<?php

namespace PS\Core\Service;

abstract class AbstractServiceManager
{
    protected array $services = [];

    public function __construct()
    {
        pcntl_async_signals(true);
        $this->registerServices();
        $this->startServices();
        $this->monitorProgress();
    }

    abstract protected function registerServices(): void;

    protected function addService(AbstractService $service): void
    {
        $this->services[] = $service;
    }

    protected function startServices(): void
    {
        foreach ($this->services as $service) {
            $pid = pcntl_fork();

            if ($pid == -1) {
                die("Cannot fork\n");
            } elseif ($pid === 0) {
                $service->run();
                exit(0);
            }
        }
    }

    protected function monitorProgress(): void
    {
        echo "Monitoring started...\n";

        while (true) {
            system('clear');
            echo "Progress:\n";

            $done = 0;
            foreach ($this->services as $index => $service) {
                $shmId = @shm_attach($service->getShmKey(), 1024);
                $progress = shm_has_var($shmId, 1) ? shm_get_var($shmId, 1) : 0;

                printf("Service #%d: [%3d%%]\n", $index + 1, $progress);

                if ($progress >= 100) {
                    $done++;
                }

                shm_detach($shmId);
            }

            if ($done === count($this->services)) {
                echo "\n✅ Done!\n";
                break;
            }

            sleep(1);
        }
    }
}
