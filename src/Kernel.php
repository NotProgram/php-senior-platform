<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function getCacheDir(): string
    {
        if (isset($_SERVER['VERCEL']) || isset($_ENV['VERCEL'])) {
            return sys_get_temp_dir() . '/symfony/cache/' . $this->environment;
        }

        return parent::getCacheDir();
    }

    public function getLogDir(): string
    {
        if (isset($_SERVER['VERCEL']) || isset($_ENV['VERCEL'])) {
            return sys_get_temp_dir() . '/symfony/log';
        }

        return parent::getLogDir();
    }

    /**
     * @return list<string> An array of allowed values for APP_ENV
     */
    private function getAllowedEnvs(): array
    {
        return ['prod', 'dev', 'test'];
    }
}
