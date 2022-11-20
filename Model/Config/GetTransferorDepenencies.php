<?php
/*
 * Copyright © GhostUnicorns. All rights reserved.
 * See LICENSE and/or COPYING.txt for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtAmqp\Model\Config;

use GhostUnicorns\CrtConfigDefinition\Model\ConfigInterface;

class GetTransferorDepenencies
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
     * @param string $transferorType
     * @return string[]
     */
    public function execute(
        string $activityType,
        string $transferorType
    ): array {
        $configDefinitions = $this->config->getAllTransferors();
        $transferorsDefinition = $configDefinitions[$activityType];
        $transferorDefinition = $transferorsDefinition[$transferorType];

        $dependenciesDefinition = array_key_exists('dependencies', $transferorDefinition) ? $transferorDefinition['dependencies'] : [];

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
