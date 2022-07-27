<?php

namespace JustBetter\DynamicsClient\Concerns;

trait CanBeSerialized
{
    public function __serialize(): array
    {
        return [
            'connection' => $this->connection,
            'endpoint' => $this->endpoint,
            'data' => $this->getIdentifierData(),
        ];
    }

    public function __unserialize(array $data): void
    {
        $this
            ->setConnection($data['connection'])
            ->setEndpoint($data['endpoint'])
            ->setData($data['data'])
            ->refresh();
    }
}
