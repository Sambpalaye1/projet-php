<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Mes tâches</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="style.css">

<?php
$taches = json_decode(@file_get_contents('tasks.json'), true);
if (!is_array($taches)) $taches = [];

function genererID() {
    return 'tache' . time() . rand(1000, 9999);
}

/* SUPPRIMER */
if (isset($_GET['sup'])) {
    $id = $_GET['sup'];
    $taches = array_values(array_filter($taches, fn($t) => $t['id'] !== $id));
    file_put_contents('tasks.json', json_encode($taches, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header('Location: taches.php');
    exit;
}

/* CHANGER ETAT */
if (isset($_GET['statut'])) {
    $id = $_GET['statut'];
    foreach ($taches as &$t) {
        if ($t['id'] === $id) {
            $liste = ['faire', 'cours', 'finie'];
            $pos = array_search($t['statut'], $liste);
            $t['statut'] = ($pos === false || $pos === 2) ? 'faire' : $liste[$pos + 1];
            break;
        }
    }
    file_put_contents('tasks.json', json_encode($taches, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header('Location: taches.php');
    exit;
}

/* FILTRES */
$filtre_statut = $_GET['filtre_statut'] ?? '';
$filtre_prio   = $_GET['filtre_prio'] ?? '';
$recherche     = trim($_GET['recherche'] ?? '');

$taches_affichees = $taches;

if ($filtre_statut) {
    $taches_affichees = array_filter($taches_affichees, fn($t) => $t['statut'] === $filtre_statut);
}
if ($filtre_prio) {
    $taches_affichees = array_filter($taches_affichees, fn($t) => $t['prio'] === $filtre_prio);
}
if ($recherche) {
    $taches_affichees = array_filter($taches_affichees, fn($t) =>
        stripos($t['titre'], $recherche) !== false ||
        stripos($t['desc'] ?? '', $recherche) !== false
    );
}

/* EDIT */
$edit = [];
if (isset($_GET['edit'])) {
    foreach ($taches as $t) {
        if ($t['id'] === $_GET['edit']) {
            $edit = $t;
            break;
        }
    }
}

/* AJOUT / MODIF */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre']);
    $desc  = trim($_POST['desc']);
    $prio  = $_POST['prio'];
    $date  = $_POST['date'];

    if ($titre !== '') {
        if (isset($_GET['edit'])) {
            foreach ($taches as &$t) {
                if ($t['id'] === $_GET['edit']) {
                    $t['titre'] = $titre;
                    $t['desc']  = $desc;
                    $t['prio']  = $prio;
                    $t['date']  = $date;
                    break;
                }
            }
        } else {
            $taches[] = [
                'id' => genererID(),
                'titre' => $titre,
                'desc' => $desc,
                'prio' => $prio,
                'statut' => 'faire',
                'date_creation' => date('Y-m-d H:i:s'),
                'date' => $date
            ];
        }
        file_put_contents('tasks.json', json_encode($taches, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        header('Location: taches.php');
        exit;
    }
}

/* STATS */
$total = count($taches);
$terminees = count(array_filter($taches, fn($t) => $t['statut'] === 'finie'));
$pourcent = $total ? round(($terminees / $total) * 100) : 0;
$retard = count(array_filter($taches, fn($t) =>
    !empty($t['date']) && $t['statut'] !== 'finie' && strtotime($t['date']) < strtotime('today')
));

$statuts = [
    'faire' => 'À faire',
    'cours' => 'En cours',
    'finie' => 'Terminée'
];
?>
</head>

<body>
<div class="container mt-4">

<h1 class="titre">Gestion des tâches</h1>

<div class="row mb-4">
<div class="col-md-3"><div class="card box"><div class="card-body"><h5>Total</h5><p class="nombre"><?= $total ?></p></div></div></div>
<div class="col-md-3"><div class="card box"><div class="card-body"><h5>Terminées</h5><p class="nombre"><?= $terminees ?></p></div></div></div>
<div class="col-md-3"><div class="card box"><div class="card-body"><h5>Progression</h5><p class="nombre"><?= $pourcent ?>%</p></div></div></div>
<div class="col-md-3"><div class="card box boxretard"><div class="card-body"><h5>En retard</h5><p class="nombre"><?= $retard ?></p></div></div></div>
</div>

<div class="row">
<div class="col-md-4">
<div class="card">
<div class="entete">Ajouter / Modifier</div>
<div class="card-body">
<form method="post">
<input class="form-control mb-2" name="titre" placeholder="Titre" value="<?= htmlspecialchars($edit['titre'] ?? '') ?>" required>
<textarea class="form-control mb-2" name="desc" placeholder="Description"><?= htmlspecialchars($edit['desc'] ?? '') ?></textarea>
<select class="form-select mb-2" name="prio">
<option value="basse">basse</option>
<option value="moyenne">moyenne</option>
<option value="haute">haute</option>
</select>
<input type="date" class="form-control mb-2" name="date" value="<?= htmlspecialchars($edit['date'] ?? '') ?>" required>
<button class="btn btn-success w-100">Enregistrer</button>
</form>
</div>
</div>
</div>

<div class="col-md-8">
<table class="table table-bordered">
<thead>
<tr>
<th>Titre</th><th>Priorité</th><th>État</th><th>Date limite</th><th>Créée</th><th>Actions</th>
</tr>
</thead>
<tbody>

<?php foreach ($taches_affichees as $t):
$enretard = !empty($t['date']) && $t['statut'] !== 'finie' && strtotime($t['date']) < strtotime('today');
?>
<tr class="<?= $enretard ? 'retard' : '' ?>">
<td><strong><?= htmlspecialchars($t['titre']) ?></strong><div class="small text-muted"><?= htmlspecialchars($t['desc'] ?? '') ?></div></td>
<td><span class="<?= $t['prio'] ?>"><?= $t['prio'] ?></span></td>
<td><span class="etat <?= $t['statut'] ?>"><?= $statuts[$t['statut']] ?></span></td>
<td><?= htmlspecialchars($t['date']) ?></td>
<td class="small"><?= htmlspecialchars($t['date_creation']) ?></td>
<td>
<a class="btn btn-warning btn-sm" href="?edit=<?= $t['id'] ?>">Modifier</a>
<a class="btn btn-info btn-sm" href="?statut=<?= $t['id'] ?>">État</a>
<a class="btn btn-danger btn-sm" href="?sup=<?= $t['id'] ?>" onclick="return confirm('Supprimer ?')">Supprimer</a>
</td>
</tr>
<?php endforeach; ?>

<?php if (empty($taches_affichees)): ?>
<tr><td colspan="6" class="text-center">Aucune tâche</td></tr>
<?php endif; ?>

</tbody>
</table>
</div>
</div>
</div>
</body>
</html>
