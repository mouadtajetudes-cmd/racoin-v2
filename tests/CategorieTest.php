<?php

namespace Tests;

use model\Categorie;
use PHPUnit\Framework\TestCase;

class CategorieTest extends TestCase
{
    public function testCreationCategorie()
    {
        $categorie = new Categorie();
        $categorie->id_categorie = 1;
        $categorie->nom_categorie = "Informatique";

        $this->assertEquals(1, $categorie->id_categorie);
        $this->assertEquals("Informatique", $categorie->nom_categorie);
    }

    public function testCategorieTableName()
    {
        $categorie = new Categorie();
        $this->assertEquals('categorie', $categorie->getTable());
    }
    public function testModificationCategorie()
    {
        $categorie = new Categorie();
        $categorie->id_categorie = 1;
        $categorie->nom_categorie = "Informatique";

        $categorie->id_categorie = 2;
        $categorie->nom_categorie = "Électronique";

        $this->assertEquals(2, $categorie->id_categorie);
        $this->assertEquals("Électronique", $categorie->nom_categorie);
    }
}
