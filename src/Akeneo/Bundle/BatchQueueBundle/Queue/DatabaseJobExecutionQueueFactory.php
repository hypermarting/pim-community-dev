<?php

declare(strict_types=1);

namespace Akeneo\Bundle\BatchQueueBundle\Queue;

use Akeneo\Bundle\BatchQueueBundle\Hydrator\JobExecutionMessageHydrator;
use Akeneo\Component\BatchQueue\Queue\JobExecutionQueueInterface;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Provider\NodeProviderInterface;

/**
 * Factory to create a database job execution queue.
 *
 * @author    Alexandre Hocquard <alexandre.hocquard@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class DatabaseJobExecutionQueueFactory
{
    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var JobExecutionMessageRepository */
    protected $jobExecutionMessageRepository;

    /**
     * @param EntityManagerInterface        $entityManager
     * @param JobExecutionMessageRepository $jobExecutionMessageRepository
     */
    public function __construct(EntityManagerInterface $entityManager, JobExecutionMessageRepository $jobExecutionMessageRepository)
    {
        $this->entityManager = $entityManager;
        $this->jobExecutionMessageRepository = $jobExecutionMessageRepository;
    }

    public function createQueue(string $consumer): JobExecutionQueueInterface
    {
        return new DatabaseJobExecutionQueue($this->entityManager, $this->jobExecutionMessageRepository, $consumer);
    }
}
