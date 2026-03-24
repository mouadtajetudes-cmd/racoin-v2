<?php

namespace Tests;

use model\Departement;
use PHPUnit\Framework\TestCase;

class DepartementTest extends TestCase
{
    public function testCreationDepartement()
    {
        $departement = new Departement();
        $departement->id_departement = 1;
        $departement->nom_departement = "Informatique";

        $this->assertEquals(1, $departement->id_departement);
        $this->assertEquals("Informatique", $departement->nom_departement);
    }
    public function testGetDepartementRegion()
    {
        $departement = new Departement();
        $departement->id_region = 1;

        $this->assertEquals(1, $departement->id_region);
    }
    public function testModificationDepartement()
    {
        $departement = new Departement();
        $departement->id_departement = 1;
        $departement->nom_departement = "Informatique";

        $departement->id_departement = 2;
        $departement->nom_departement = "Électronique";

        $this->assertEquals(2, $departement->id_departement);
        $this->assertEquals("Électronique", $departement->nom_departement);
    }
}
