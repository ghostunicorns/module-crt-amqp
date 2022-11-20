<?php
/*
 * Copyright © GhostUnicorns. All rights reserved.
 * See LICENSE and/or COPYING.txt for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtAmqp\Model\Config;

use GhostUnicorns\CrtConfigDefinition\Model\ConfigInterface;

class GetCollectorDepenencies
{

    /**
     * @param ConfigInterface $config
     */
    public function __construct(
        ConfigInterface $config
    ) {
        $this->config = $config;
    }

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @param string $activityType
     * @param string $collectorType
     * @return string[]
     */
    public function execute(
        string $activityType,
        string $collectorType
    ): array {
        $configDefinitions = $this->config->getAllCollectors();
        $collectorsDefinition = $configDefinitions[$activityType];
        $collectorDefinition = $collectorsDefinition[$collectorType];

        $dependenciesDefinition = array_key_exists('dependencies', $collectorDefinition) ? $collectorDefinition['dependencies'] : [];

        $dependencies = [];
        if (!array_key_exists('0', $dependenciesDefinition)) {
            return [];
        }

        foreach ($dependenciesDefinition[0] as $dependencyDefinition) {
            $dependencies[] = $dependencyDefinition[0]['name'];
        }

        return $dependencies;
    }
}
