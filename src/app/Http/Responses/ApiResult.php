<?php

namespace App\Http\Responses;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;

class ApiResult implements Arrayable, Jsonable, JsonSerializable
{
    private bool $success;
    private mixed $errors;

    private function __construct(bool $success, mixed $errors)
    {
        $this->success = $success;
        $this->errors = $errors;
    }

    /**
     * Create a successful result
     */
    public static function success(): self
    {
        return new self(true, null);
    }

    /**
     * Create a failed result with errors
     */
    public static function error(mixed $errors): self
    {
        return new self(false, $errors);
    }

    /**
     * Get the errors
     */
    public function getErrors(): mixed
    {
        return $this->errors;
    }

    /**
     * Check if the result is successful
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'errors' => $this->errors,
        ];
    }

    /**
     * Convert to JSON
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Specify data which should be serialized to JSON
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}