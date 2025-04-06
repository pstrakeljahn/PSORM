<?php

namespace PS\Core\Service;

abstract class AbstractService
{
    protected int $sleep = 1;
    protected int $currentProgress = 0;

    protected int $shmKey;

    public function __construct()
    {
        $this->shmKey = ftok(__FILE__, 's') + rand(1, 999);
    }

    public function getShmKey(): int
    {
        return $this->shmKey;
    }

    public function run(): void
    {
        $shmId = shm_attach($this->shmKey, 1024);

        while ($this->currentProgress < 100) {
            $this->handle();

            $this->currentProgress += rand(1, 10);
            if ($this->currentProgress > 100) {
                $this->currentProgress = 100;
            }

            shm_put_var($shmId, 1, $this->currentProgress);

            sleep($this->sleep);
        }

        shm_put_var($shmId, 1, 100);
        shm_detach($shmId);
    }

    abstract protected function handle(): void;
}
