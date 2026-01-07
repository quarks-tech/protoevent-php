<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\Transport;

use QuarksTech\ProtoEvent\EventBus\ServiceInfo;

/**
 * Interface for transports that support topology setup
 */
interface SetuperInterface
{
    /**
     * Setup topology for the consumer
     *
     * @param string $consumerName Consumer name (used for queue naming)
     * @param ServiceInfo[] $serviceInfos Services to subscribe to
     */
    public function setup(string $consumerName, array $serviceInfos): void;
}
