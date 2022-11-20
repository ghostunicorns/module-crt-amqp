<?php
/*
 * Copyright © GhostUnicorns. All rights reserved.
 * See LICENSE and/or COPYING.txt for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtAmqp\Model\Status;

use GhostUnicorns\CrtActivity\Model\ActivityStateInterface;
use GhostUnicorns\CrtAmqp\Model\AmqpStateInterface;
use GhostUnicorns\CrtConfigDefinition\Model\ConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use GhostUnicorns\CrtActivity\Api\ActivityRepositoryInterface;
use GhostUnicorns\CrtAmqpCollector\Api\CollectorRepositoryInterface;

class ActivityCheckIsTimeToMakeAsCollectedDequeued
{
    /**
     * @var ActivityRepositoryInterface
     */
    private $activityRepository;

    /**
     * @var CollectorRepositoryInterface
     */
    private $collectorRepository;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @param ActivityRepositoryInterface $activityRepository
     * @param CollectorRepositoryInterface $collectorRepository
     * @param ConfigInterface $config
     */
    public function __construct(
        ActivityRepositoryInterface $activityRepository,
        CollectorRepositoryInterface $collectorRepository,
        ConfigInterface $config
    ) {
        $this->activityRepository = $activityRepository;
        $this->collectorRepository = $collectorRepository;
        $this->config = $config;
    }

    /**
     * @param int $activityId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function execute(int $activityId): bool
    {
        $activity = $this->activityRepository->getById($activityId);
        $activityType = $activity->getType();

        $amqpCollectors = $this->collectorRepository->getAllByActivityId($activityId);
        $configDefinitions = $this->config->getAllCollectors();
        $collectorsDefinition = $configDefinitions[$activityType];

        foreach ($collectorsDefinition as $collectorDefinitionType => $collectorDefinition) {
            $isDone = false;
            foreach ($amqpCollectors as $amqpCollector) {
                if (
                    $amqpCollector->getCollectorType() === $collectorDefinitionType &&
                    $amqpCollector->getStatus() === AmqpStateInterface::DEQUEUED
                ) {
                    $isDone = true;
                }
            }
            if (!$isDone) {
                return false;
            }
        }

        $activity->setStatus(ActivityStateInterface::COLLECTED);
        $this->activityRepository->save($activity);

        return true;
    }
}
