<?php

namespace service;

use model\Departement;

class DepartmentService
{

    protected $departments = array();

    public function getAllDepartments()
    {
        return Departement::orderBy('nom_departement')->get()->toArray();
    }
}
