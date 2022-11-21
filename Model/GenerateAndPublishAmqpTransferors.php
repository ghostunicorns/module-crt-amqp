<?php
/*
 * Copyright © GhostUnicorns. All rights reserved.
 * See LICENSE and/or COPYING.txt for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtAmqp\Model;

use GhostUnicorns\CrtActivity\Api\ActivityRepositoryInterface;
use GhostUnicorns\CrtAmqp\Model\Config\GetTransferorDepenencies;
use GhostUnicorns\CrtAmqp\Model\Publisher\TransferorPublisher;
use GhostUnicorns\CrtAmqpTransferor\Api\TransferorRepositoryInterface;
use GhostUnicorns\CrtAmqpTransferor\Model\TransferorModel;
use GhostUnicorns\CrtBase\Api\CrtListInterface;
use GhostUnicorns\CrtBase\Exception\CrtException;
use Magento\Framework\Exception\NoSuchEntityException;

class GenerateAndPublishAmqpTransferors
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
     * @var TransferorRepositoryInterface
     */
    private $transferorRepository;

    /**
     * @var TransferorPublisher
     */
    private $transferorPublisher;

    /**
     * @var GetTransferorDepenencies
     */
    private $getTransferorDepenencies;

    /**
     * @param ActivityRepositoryInterface $activityRepository
     * @param CrtListInterface $crtList
     * @param TransferorRepositoryInterface $transferorRepository
     * @param TransferorPublisher $transferorPublisher
     * @param GetTransferorDepenencies $getTransferorDepenencies
     */
    public function __construct(
        ActivityRepositoryInterface $activityRepository,
        CrtListInterface $crtList,
        TransferorRepositoryInterface $transferorRepository,
        TransferorPublisher $transferorPublisher,
        GetTransferorDepenencies $getTransferorDepenencies
    ) {
        $this->activityRepository = $activityRepository;
        $this->crtList = $crtList;
        $this->transferorRepository = $transferorRepository;
        $this->transferorPublisher = $transferorPublisher;
        $this->getTransferorDepenencies = $getTransferorDepenencies;
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

        $transferorList = $this->crtList->getTransferorList($activityType);
        $transferors = $transferorList->getTransferors();

        foreach ($transferors as $transferorType => $transferor) {
            $amqpTransferors = $this->transferorRepository->getAllByActivityId($activityId);
            $transferorName = $transferor['name'];

            $dependencies = $this->getTransferorDepenencies->execute($activityType, $transferorType);

            if ($this->notExists($amqpTransferors, $dependencies) ||
                $this->isCollected($amqpTransferors, $transferorName) ||
                $this->hasNotCollectedDependency($amqpTransferors, $dependencies)) {
                continue;
            }

            $this->transferorRepository->createOrUpdate(
                $activityId,
                $transferorType,
                AmqpStateInterface::ADDED_TO_QUEUE
            );

            $this->transferorPublisher->execute($activityId, $transferorType);
        }
    }

    /**
     * @param TransferorModel[] $amqpTransferors
     * @param string[] $dependencies
     * @return bool
     */
    private function notExists(array $amqpTransferors, array $dependencies): bool
    {
        return !count($amqpTransferors) && count($dependencies);
    }

    /**
     * @param TransferorModel[] $amqpTransferors
     * @param string $transferorName
     * @return bool
     */
    private function isCollected(array $amqpTransferors, string $transferorName): bool
    {
        foreach ($amqpTransferors as $amqpTransferor) {
            if ($amqpTransferor->getTransferorType() === $transferorName) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param TransferorModel[] $amqpTransferors
     * @param string[] $dependencies
     * @return bool
     */
    private function hasNotCollectedDependency(
        array $amqpTransferors,
        array $dependencies
    ): bool {
        foreach ($dependencies as $dependency) {
            $isInAmqpTransferorTable = false;
            foreach ($amqpTransferors as $amqpTransferor) {
                if ($amqpTransferor->getTransferorType() === $dependency) {
                    $isInAmqpTransferorTable = true;

                    if ($amqpTransferor->getStatus() !== AmqpStateInterface::DEQUEUED) {
                        return true;
                    }
                }
            }
            if (!$isInAmqpTransferorTable) {
                return true;
            }
        }
        return false;
    }
}
