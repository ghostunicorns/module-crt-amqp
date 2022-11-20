<?php
/*
 * Copyright © GhostUnicorns. All rights reserved.
 * See LICENSE and/or COPYING.txt for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtAmqp\Model\ResourceModel;

use Exception;
use GhostUnicorns\CrtActivity\Api\ActivityRepositoryInterface;
use GhostUnicorns\CrtActivity\Model\ActivityModelFactory;
use GhostUnicorns\CrtActivity\Model\ActivityStateInterface;
use GhostUnicorns\CrtBase\Exception\CrtException;
use GhostUnicorns\CrtConfigDefinition\Model\ConfigInterface;

class SetAmqpActivity
{
    /**
     * @var ActivityRepositoryInterface
     */
    private $activityRepository;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var ActivityModelFactory
     */
    private ActivityModelFactory $activityModelFactory;

    /**
     * @param ActivityRepositoryInterface $activityRepository
     * @param ConfigInterface $config
     * @param ActivityModelFactory $activityModelFactory
     */
    public function __construct(
        ActivityRepositoryInterface $activityRepository,
        ConfigInterface $config,
        ActivityModelFactory $activityModelFactory
    ) {
        $this->activityRepository = $activityRepository;
        $this->config = $config;
        $this->activityModelFactory = $activityModelFactory;
    }

    /**
     * @param string $type
     * @param string $extra
     * @return int
     * @throws CrtException
     * @throws Exception
     */
    public function execute(string $type, string $extra): int
    {
        $config = $this->config->getTypeConfig($type);
        if (!$config->isEnabled()) {
            throw new CrtException(__('The requested Crt type is disabled by configuration: %1', $type));
        }

        try {
            $activity = $this->activityModelFactory->create();
            $activity->setType($type);
            $activity->setStatus(ActivityStateInterface::COLLECTING);
            if ($extra !== '') {
                $activity->addExtraArray(['data' => $extra]);
            }
            $this->activityRepository->save($activity);

            return (int)$activity->getId();
        } catch (Exception $e) {
            if (isset($activity)) {
                $activity->setStatus(ActivityStateInterface::COLLECT_ERROR);
                $activity->addExtraArray(['error' => $e->getMessage()]);
                $this->activityRepository->save($activity);
            }
            throw $e;
        }
    }
}
