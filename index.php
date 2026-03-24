<?php
require 'vendor/autoload.php';

use controller\AnnoncesController;
use controller\ItemController;
use controller\KeyGeneratorController;
use controller\SearchController;
use db\connection as Connection;
use model\Annonce;
use model\Categorie;
use model\Annonceur;
use model\Departement;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use service\AnnonceurService;
use service\CategorieService;
use service\DepartmentService;
use service\ItemService;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;


Connection::createConn();

// Initialisation de Slim
// $app = new App([
//     'settings' => [
//         'displayErrorDetails' => true,
//     ],
// ]);
$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);
// Initialisation de Twig
$loader = new FilesystemLoader(__DIR__ . '/template');
$twig   = new Environment($loader);

// Ajout d'un middleware pour le trailing slash
$app->add(function (ServerRequestInterface $request, RequestHandlerInterface $handler) use ($app): ResponseInterface {
    $uri  = $request->getUri();
    $path = $uri->getPath();
    if ($path != '/' && str_ends_with($path, '/')) {
        $uri = $uri->withPath(substr($path, 0, -1));
        if ($request->getMethod() == 'GET') {
            return $app->getResponseFactory()
                ->createResponse(301)
                ->withHeader('Location', (string) $uri);
        }
        return $handler->handle($request->withUri($uri));
    }
    return $handler->handle($request);
});


if (!isset($_SESSION)) {
    session_start();
    $_SESSION['formStarted'] = true;
}

if (!isset($_SESSION['token'])) {
    $token                  = md5(uniqid(rand(), TRUE));
    $_SESSION['token']      = $token;
    $_SESSION['token_time'] = time();
} else {
    $token = $_SESSION['token'];
}

$menu = [
    [
        'href' => './index.php',
        'text' => 'Accueil'
    ]
];

$chemin = dirname($_SERVER['SCRIPT_NAME']);

$cat = new CategorieService();
$dpt = new DepartmentService();

$app->get('/', function ($request, $response) use ($twig, $menu, $chemin, $cat) {
    $index = new AnnoncesController();
    $index->displayAllAnnonce($twig, $menu, $chemin, $cat->getCategories());
    return $response;
});

$app->get('/item/{n}', function ($request, $response, $arg) use ($twig, $menu, $chemin, $cat) {
    $n     = $arg['n'];
    $item = new ItemController();
    $item->afficherItem($twig, $menu, $chemin, $n, $cat->getCategories());
    return $response;
});

$app->get('/add', function ($request, $response) use ($twig, $app, $menu, $chemin, $cat, $dpt) {
    $ajout = new ItemService();
    $ajout->addItemView($twig, $menu, $chemin, $cat->getCategories(), $dpt->getAllDepartments());
    return $response;
});

$app->post('/add', function ($request, $response) use ($twig, $app, $menu, $chemin) {
    $allPostVars = $request->getParsedBody();
    $ajout       = new ItemService();
    $ajout->addNewItem($twig, $menu, $chemin, $allPostVars);
    return $response;
});

$app->get('/item/{id}/edit', function ($request, $response, $arg) use ($twig, $menu, $chemin) {
    $id   = $arg['id'];
    $item = new ItemController();
    $item->modifyGet($twig, $menu, $chemin, $id);
    return $response;
});
$app->post('/item/{id}/edit', function ($request, $response, $arg) use ($twig, $app, $menu, $chemin, $cat, $dpt) {
    $id          = $arg['id'];
    $allPostVars = $request->getParsedBody();
    $item        = new ItemController();
    $item->modifyPost($twig, $menu, $chemin, $id, $allPostVars, $cat->getCategories(), $dpt->getAllDepartments());
    return $response;
});

$app->map(['GET', 'POST'], '/item/{id}/confirm', function ($request, $response, $arg) use ($twig, $app, $menu, $chemin) {
    $id   = $arg['id'];
    $allPostVars = $request->getParsedBody();
    $item        = new ItemController();
    $item->edit($twig, $menu, $chemin, $id, $allPostVars);
    return $response;
});

$app->get('/search', function ($request, $response) use ($twig, $menu, $chemin, $cat) {
    $s = new SearchController();
    $s->show($twig, $menu, $chemin, $cat->getCategories());
    return $response;
});


$app->post('/search', function ($request, $response) use ($app, $twig, $menu, $chemin, $cat) {
    $array = $request->getParsedBody();
    $s     = new SearchController();
    $s->research($array, $twig, $menu, $chemin, $cat->getCategories());
    return $response;
});

$app->get('/annonceur/{n}', function ($request, $response, $arg) use ($twig, $menu, $chemin, $cat) {
    $n         = $arg['n'];
    $annonceur = new AnnonceurService();
    $annonceur->afficherAnnonceur($twig, $menu, $chemin, $n, $cat->getCategories());
    return $response;
});

$app->get('/del/{n}', function ($request, $response, $arg) use ($twig, $menu, $chemin) {
    $n    = $arg['n'];
    $item = new ItemController();
    $item->supprimerItemGet($twig, $menu, $chemin, $n);
    return $response;
});

$app->post('/del/{n}', function ($request, $response, $arg) use ($twig, $menu, $chemin, $cat) {
    $n    = $arg['n'];
    $item = new ItemController();
    $item->supprimerItemPost($twig, $menu, $chemin, $n, $cat->getCategories());
    return $response;
});

$app->get('/cat/{n}', function ($request, $response, $arg) use ($twig, $menu, $chemin, $cat) {
    $n = $arg['n'];
    $categorie = new CategorieService();
    $categorie->displayCategorie($twig, $menu, $chemin, $cat->getCategories(), $n);
    return $response;
});

$app->get('/api(/)', function ($request, $response) use ($twig, $menu, $chemin, $cat) {
    $template = $twig->load('api.html.twig');
    $menu     = array(
        array(
            'href' => $chemin,
            'text' => 'Acceuil'
        ),
        array(
            'href' => $chemin . '/api',
            'text' => 'Api'
        )
    );
    echo $template->render(array('breadcrumb' => $menu, 'chemin' => $chemin));
    return $response;
});

$app->group('/api', function (RouteCollectorProxy $group) use ($twig, $menu, $chemin, $cat) {

    $group->group('/annonce', function (RouteCollectorProxy $group) {

        $group->get('/{id}', function ($request, $response, $arg) {
            $id          = $arg['id'];
            $annonceList = ['id_annonce', 'id_categorie as categorie', 'id_annonceur as annonceur', 'id_departement as departement', 'prix', 'date', 'titre', 'description', 'ville'];
            $return      = Annonce::select($annonceList)->find($id);

            if (isset($return)) {
                $return->categorie     = Categorie::find($return->categorie);
                $return->annonceur     = Annonceur::select('email', 'nom_annonceur', 'telephone')
                    ->find($return->annonceur);
                $return->departement   = Departement::select('id_departement', 'nom_departement')->find($return->departement);
                $links                 = [];
                $links['self']['href'] = '/api/annonce/' . $return->id_annonce;
                $return->links         = $links;
                $response->getBody()->write($return->toJson());
                return $response->withHeader('Content-Type', 'application/json');
            }

            return $response->withStatus(404);
        });
    });

    $group->group('/annonces', function (RouteCollectorProxy $group) {

        $group->get('', function ($request, $response) {
            $annonceList = ['id_annonce', 'prix', 'titre', 'ville'];
            $a     = Annonce::all($annonceList);
            $links = [];
            foreach ($a as $ann) {
                $links['self']['href'] = '/api/annonce/' . $ann->id_annonce;
                $ann->links            = $links;
            }
            $response->getBody()->write($a->toJson());
            return $response->withHeader('Content-Type', 'application/json');
        });

        $group->get('/', function ($request, $response) {
            return $response
                ->withHeader('Location', '/api/annonces')
                ->withStatus(301);
        });
    });


    $group->group('/categorie', function (RouteCollectorProxy $group) {

        $group->get('/{id}', function ($request, $response, $arg) {
            $id = $arg['id'];
            $a     = Annonce::select('id_annonce', 'prix', 'titre', 'ville')
                ->where('id_categorie', '=', $id)
                ->get();
            $links = [];

            foreach ($a as $ann) {
                $links['self']['href'] = '/api/annonce/' . $ann->id_annonce;
                $ann->links            = $links;
            }

            $c                     = Categorie::find($id);
            $links['self']['href'] = '/api/categorie/' . $id;
            $c->links              = $links;
            $c->annonces           = $a;
            $response->getBody()->write($c->toJson());
            return $response->withHeader('Content-Type', 'application/json');
        });
    });

    $group->group('/categories', function (RouteCollectorProxy $group) {
        $group->get('', function ($request, $response) {
            $c     = Categorie::get();
            $links = [];
            foreach ($c as $cat) {
                $links['self']['href'] = '/api/categorie/' . $cat->id_categorie;
                $cat->links            = $links;
            }
            $response->getBody()->write($c->toJson());
            return $response->withHeader('Content-Type', 'application/json');
        });

        $group->get('/', function ($request, $response) {
            return $response
                ->withHeader('Location', '/api/categories')
                ->withStatus(301);
        });
    });

    $group->get('/key', function ($request, $response) use ($twig, $menu, $chemin, $cat) {
        $kg = new KeyGeneratorController();
        $kg->show($twig, $menu, $chemin, $cat->getCategories());
        return $response;
    });

    $group->post('/key', function ($request, $response) use ($twig, $menu, $chemin, $cat) {
        $nom = $_POST['nom'];

        $kg = new KeyGeneratorController();
        $kg->generateKey($twig, $menu, $chemin, $cat->getCategories(), $nom);
        return $response;
    });
});


$app->run();
