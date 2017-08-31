<?php

declare(strict_types=1);

namespace Pim\Bundle\BatchQueueBundle\tests\integration\Command;

use Akeneo\Component\Batch\Job\BatchStatus;
use Akeneo\Component\Batch\Job\ExitStatus;
use Akeneo\Component\Batch\Model\JobExecution;
use Akeneo\Component\BatchQueue\Queue\JobExecutionMessage;
use Akeneo\Component\BatchQueue\Queue\JobExecutionQueueInterface;
use Akeneo\Test\Integration\Configuration;
use Akeneo\Test\Integration\TestCase;
use Doctrine\DBAL\Driver\Connection;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class JobQueueConsumerCommandIntegration extends TestCase
{
    public function testLaunchAJobExecution()
    {
        $jobExecution = $this->createJobExecution('csv_product_export', 'mary');

        $jobExecutionMessage = new JobExecutionMessage();
        $jobExecutionMessage
            ->setOptions(['email' => 'ziggy@akeneo.com', 'env' => $this->getParameter('kernel.environment')])
            ->setJobExecutionId($jobExecution->getId());

        $this->getQueue()->publish($jobExecutionMessage);

        $output = $this->launchConsumer();

        $standardOutput = $output->fetch();

        $this->assertContains(sprintf('Job execution "%s" is finished.', $jobExecution->getId()), $standardOutput);

        $stmt = $this->getConnection()->prepare('SELECT status, exit_code, health_check_time from akeneo_batch_job_execution where id = :id');
        $stmt->bindValue('id', $jobExecution->getId());
        $stmt->execute();
        $row = $stmt->fetch();

        $this->assertEquals(BatchStatus::COMPLETED, $row['status']);
        $this->assertEquals(ExitStatus::COMPLETED, $row['exit_code']);
        $this->assertNotNull($row['health_check_time']);

        $stmt = $this->getConnection()->prepare('SELECT consumer from akeneo_batch_job_execution_queue');
        $stmt->execute();
        $row = $stmt->fetch();

        $this->assertContains('_consumer', $row['consumer']);
    }

    /**
     * @param string $jobInstanceCode
     * @param string $user
     * @param array  $configuration
     *
     * @throws \RuntimeException
     *
     * @return JobExecution
     */
    protected function createJobExecution(string $jobInstanceCode, ?string $user, array $configuration = []) : JobExecution
    {
        $jobInstanceClass = $this->getParameter('akeneo_batch.entity.job_instance.class');
        $jobInstance = $this
            ->get('doctrine.orm.default_entity_manager')
            ->getRepository($jobInstanceClass)
            ->findOneBy(['code' => $jobInstanceCode]);

        $job = $this->get('akeneo_batch.job.job_registry')->get($jobInstanceCode);

        $configuration = array_merge($jobInstance->getRawParameters(), $configuration);

        $jobParameters = $this->get('akeneo_batch.job_parameters_factory')->create($job, $configuration);

        $errors = $this->get('akeneo_batch.job.job_parameters_validator')->validate($job, $jobParameters, ['Default', 'Execution']);

        if (count($errors) > 0) {
            throw new \RuntimeException('JobExecution could not be created due to invalid job parameters.');
        }

        $jobExecution = $this->get('akeneo_batch.job_repository')->createJobExecution($jobInstance, $jobParameters);
        $jobExecution->setUser($user);
        $this->get('akeneo_batch.job_repository')->updateJobExecution($jobExecution);

        return $jobExecution;
    }

    /**
     * @return Connection
     */
    protected function getConnection(): Connection
    {
        return $this->get('doctrine.orm.entity_manager')->getConnection();
    }

    /**
     * @return JobExecutionQueueInterface
     */
    protected function getQueue(): JobExecutionQueueInterface
    {
        return $this->get('akeneo_batch_queue.queue.database_job_execution_queue');
    }

    /**
     * {@inheritdoc}
     */
    protected function getConfiguration()
    {
        return new Configuration([Configuration::getTechnicalCatalogPath()]);
    }
}
