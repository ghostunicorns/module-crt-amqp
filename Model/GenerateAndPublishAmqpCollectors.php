<?php
/*
 * Copyright © GhostUnicorns. All rights reserved.
 * See LICENSE and/or COPYING.txt for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtAmqp\Model;

use GhostUnicorns\CrtActivity\Api\ActivityRepositoryInterface;
use GhostUnicorns\CrtAmqp\Model\Config\GetCollectorDepenencies;
use GhostUnicorns\CrtAmqp\Model\Publisher\CollectorPublisher;
use GhostUnicorns\CrtAmqpCollector\Api\CollectorRepositoryInterface;
use GhostUnicorns\CrtAmqpCollector\Model\CollectorModel;
use GhostUnicorns\CrtBase\Api\CrtListInterface;
use GhostUnicorns\CrtBase\Exception\CrtException;
use Magento\Framework\Exception\NoSuchEntityException;

class GenerateAndPublishAmqpCollectors
{
    /**
     * @var ActivityRepositoryInterface
     */
    private $activityRepository;

    /**
     * @var CrtListInterface
     */
    private $crtList;

    /**
     * @var CollectorRepositoryInterface
     */
    private $collectorRepository;

    /**
     * @var CollectorPublisher
     */
    private $collectorPublisher;

    /**
     * @var GetCollectorDepenencies
     */
    private $getCollectorDepenencies;

    /**
     * @param ActivityRepositoryInterface $activityRepository
     * @param CrtListInterface $crtList
     * @param CollectorRepositoryInterface $collectorRepository
     * @param CollectorPublisher $collectorPublisher
     * @param GetCollectorDepenencies $getCollectorDepenencies
     */
    public function __construct(
        ActivityRepositoryInterface $activityRepository,
        CrtListInterface $crtList,
        CollectorRepositoryInterface $collectorRepository,
        CollectorPublisher $collectorPublisher,
        GetCollectorDepenencies $getCollectorDepenencies
    ) {
        $this->activityRepository = $activityRepository;
        $this->crtList = $crtList;
        $this->collectorRepository = $collectorRepository;
        $this->collectorPublisher = $collectorPublisher;
        $this->getCollectorDepenencies = $getCollectorDepenencies;
    }

    /**
     * @param int $activityId
     * @return void
     * @throws NoSuchEntityException|CrtException
     */
    public function execute(int $activityId): void
    {
        $activity = $this->activityRepository->getById($activityId);

        $activityType = $activity->getType();

        $collectorList = $this->crtList->getCollectorList($activityType);
        $collectors = $collectorList->getCollectors();

        foreach ($collectors as $collectorType => $collector) {
            $amqpCollectors = $this->collectorRepository->getAllByActivityId($activityId);
            $collectorName = $collector['name'];

            $dependencies = $this->getCollectorDepenencies->execute($activityType, $collectorType);

            if ($this->notExists($amqpCollectors, $dependencies) ||
                $this->isCollected($amqpCollectors, $collectorName) ||
                $this->hasNotCollectedDependency($amqpCollectors, $dependencies, $collectorName)) {
                continue;
            }

            $this->collectorPublisher->execute($activityId, $collectorType);

            $this->collectorRepository->createOrUpdate(
                $activityId,
                $collectorType,
                AmqpStateInterface::ADDED_TO_QUEUE
            );
        }
    }

    /**
     * @param CollectorModel[] $amqpCollectors
     * @param string[] $dependencies
     * @return bool
     */
    private function notExists(array $amqpCollectors, array $dependencies): bool
    {
        return !count($amqpCollectors) && count($dependencies);
    }

    /**
     * @param CollectorModel[] $amqpCollectors
     * @param string $collectorName
     * @return bool
     */
    private function isCollected(array $amqpCollectors, string $collectorName): bool
    {
        foreach ($amqpCollectors as $amqpCollector) {
            if ($amqpCollector->getCollectorType() === $collectorName) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param CollectorModel[] $amqpCollectors
     * @param string[] $dependencies
     * @return bool
     */
    private function hasNotCollectedDependency(
        array $amqpCollectors,
        array $dependencies
    ): bool {
        foreach ($dependencies as $dependency) {
            $isInAmqpCollectorTable = false;
            foreach ($amqpCollectors as $amqpCollector) {
                if ($amqpCollector->getCollectorType() === $dependency) {
                    $isInAmqpCollectorTable = true;

                    if ($amqpCollector->getStatus() !== AmqpStateInterface::DEQUEUED) {
                        return true;
                    }
                }
            }
            if (!$isInAmqpCollectorTable) {
                return true;
            }
        }
        return false;
    }
}
