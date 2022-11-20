<?php
/*
 * Copyright © GhostUnicorns. All rights reserved.
 * See LICENSE and/or COPYING.txt for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtAmqp\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    const IMPORT_AMQP_IS_ENABLED_CONFIG_PATH = 'crt/amqp/enabled';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \GhostUnicorns\CrtImporter\Model\Config
     */
    private $baseConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param \GhostUnicorns\CrtBase\Model\Config $baseConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        \GhostUnicorns\CrtBase\Model\Config $baseConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->baseConfig = $baseConfig;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->baseConfig->isEnabled() && (bool)$this->scopeConfig->getValue(
            self::IMPORT_AMQP_IS_ENABLED_CONFIG_PATH,
            ScopeInterface::SCOPE_STORE
        );
    }
}
