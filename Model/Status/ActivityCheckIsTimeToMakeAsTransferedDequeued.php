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
use GhostUnicorns\CrtAmqpTransferor\Api\TransferorRepositoryInterface;

class ActivityCheckIsTimeToMakeAsTransferedDequeued
{
    /**
     * @var ActivityRepositoryInterface
     */
    private $activityRepository;

    /**
     * @var TransferorRepositoryInterface
     */
    private $transferorRepository;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @param ActivityRepositoryInterface $activityRepository
     * @param TransferorRepositoryInterface $transferorRepository
     * @param ConfigInterface $config
     */
    public function __construct(
        ActivityRepositoryInterface $activityRepository,
        TransferorRepositoryInterface $transferorRepository,
        ConfigInterface $config
    ) {
        $this->activityRepository = $activityRepository;
        $this->transferorRepository = $transferorRepository;
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

        $amqpTransferors = $this->transferorRepository->getAllByActivityId($activityId);
        $configDefinitions = $this->config->getAllTransferors();
        $transferorsDefinition = $configDefinitions[$activityType];

        foreach ($transferorsDefinition as $transferorDefinitionType => $transferorDefinition) {
            $isDone = false;
            foreach ($amqpTransferors as $amqpTransferor) {
                if (
                    $amqpTransferor->getTransferorType() === $transferorDefinitionType &&
                    $amqpTransferor->getStatus() === AmqpStateInterface::DEQUEUED
                ) {
                    $isDone = true;
                }
            }
            if (!$isDone) {
                return false;
            }
        }

        $activity->setStatus(ActivityStateInterface::TRANSFERED);
        $this->activityRepository->save($activity);

        return true;
    }
}
