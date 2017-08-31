<?php

declare(strict_types=1);

namespace Akeneo\Bundle\BatchQueueBundle\Manager;

use Akeneo\Bundle\BatchQueueBundle\Hydrator\JobExecutionMessageHydrator;
use Akeneo\Component\Batch\Job\BatchStatus;
use Akeneo\Component\Batch\Job\ExitStatus;
use Akeneo\Component\BatchQueue\Queue\JobExecutionMessage;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Repository to manage the status of a job.
 *
 * As it used by a daemon, it uses directly the DBAL to avoid any memory leak or connection problem due to the Unit of Work.
 *
 * @author    Alexandre Hocquard <alexandre.hocquard@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class JobExecutionManager
{
    /** @var EntityManagerInterface */
    protected $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Get the exit status of job execution associated to a job execution message.
     *
     * @param JobExecutionMessage $jobExecutionMessage
     *
     * @return ExitStatus|null
     */
    public function getExitStatus(JobExecutionMessage $jobExecutionMessage): ?ExitStatus
    {
        $sql = 'SELECT je.exit_code FROM akeneo_batch_job_execution je WHERE je.id = :id';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue('id', $jobExecutionMessage->getJobExecutionId());
        $stmt->execute();
        $row = $stmt->fetch();

        return isset($row['exit_code']) ? new ExitStatus($row['exit_code']) : null;
    }

    /**
     * Update the status of a job execution associated to a job execution message.
     *
     * @param JobExecutionMessage $jobExecutionMessage
     */
    public function markAsFailed(JobExecutionMessage $jobExecutionMessage): void
    {
        $sql = <<<SQL
UPDATE 
    akeneo_batch_job_execution je
SET 
    je.status = :status,
    je.exit_code = :exit_code,
    je.updated_time = :updated_time
WHERE
    je.id = :id;
SQL;

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue('id', $jobExecutionMessage->getJobExecutionId());
        $stmt->bindValue('status', BatchStatus::FAILED);
        $stmt->bindValue('exit_code', ExitStatus::FAILED);
        $stmt->bindValue('updated_time', new \DateTime('now', new \DateTimeZone('UTC')), Type::DATETIME);
        $stmt->execute();
    }

    /**
     * Update the health check of the job execution associated to a job execution message.
     *
     * @param JobExecutionMessage $jobExecutionMessage
     */
    public function updateHealthCheck(JobExecutionMessage $jobExecutionMessage): void
    {
        $sql = <<<SQL
UPDATE 
    akeneo_batch_job_execution je
SET 
    je.health_check_time = :health_check_time,
    je.updated_time = :updated_time
WHERE
    je.id = :id;
SQL;

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue('id', $jobExecutionMessage->getJobExecutionId());
        $stmt->bindValue('health_check_time', new \DateTime('now', new \DateTimeZone('UTC')), Type::DATETIME);
        $stmt->bindValue('updated_time', new \DateTime('now', new \DateTimeZone('UTC')), Type::DATETIME);
        $stmt->execute();
    }

    /**
     * Gets the connection. If the connection is closed, it re-opens it automatically.
     *
     * @return Connection
     */
    protected function getConnection(): Connection
    {
        $connection = $this->entityManager->getConnection();

        if (false === $connection->ping()) {
            $connection->close();
            $connection->connect();
        }

        return $connection;
    }
}
