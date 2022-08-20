<?php

namespace Phore\ServiceException\Type;

class T_ServiceException
{

    public bool $error = true;

    public string $message;

    public string $code;

    public string $description;

    public string $service;

    public array $errors = [];

    public array $trace = [];

}
