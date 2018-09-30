<?php

namespace JakubOnderka\PhpParallelLint\Process;

class PhpProcess extends Process
{
    /**
     * @param PhpExecutable $phpExecutable
     * @param array $parameters
     * @param string|null $stdIn
     * @throws \JakubOnderka\PhpParallelLint\RunTimeException
     */
    public function __construct(PhpExecutable $phpExecutable, array $parameters = array(), $stdIn = null)
    {
        $constructedParameters = $this->constructParameters($parameters, $phpExecutable->isIsHhvmType());
        parent::__construct($phpExecutable->getPath(), $constructedParameters, $stdIn);
    }

    /**
     * @param array $parameters
     * @param bool $isHhvm
     * @return array
     */
    private function constructParameters(array $parameters, $isHhvm)
    {
        if ($isHhvm) {
            $parameters = array_merge(array('-php'), $parameters);
        }

        return $parameters;
    }
}
