<?php

namespace App\Services;

interface AppServiceInterface
{
    public function dataTable($filter);

    public function getById($id);

    public function create($data);

    public function update($id, $data);

    public function delete($id);
}