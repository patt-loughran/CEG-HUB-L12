<?php

namespace App\Http\Responses;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;

class ApiResponse implements Arrayable, Jsonable, JsonSerializable
{
    private mixed $data;
    private mixed $errors;

    private function __construct(mixed $data, mixed $errors)
    {
        $this->data = $data;
        $this->errors = $errors;
    }

    /**
     * Create a successful response with data
     */
    public static function success(mixed $data): self
    {
        return new self($data, null);
    }

    /**
     * Create an error response with errors
     */
    public static function error(mixed $errors): self
    {
        return new self(null, $errors);
    }

    /**
     * Get the data
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * Get the errors
     */
    public function getErrors(): mixed
    {
        return $this->errors;
    }

    /**
     * Check if the response is successful
     */
    public function isSuccess(): bool
    {
        return $this->data !== null;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'data' => $this->data,
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