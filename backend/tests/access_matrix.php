<?php
/**
 * OptiPoint — matrice d'accès (CLI)
 *
 * Usage :
 *   C:\xampp\php\php.exe backend\tests\access_matrix.php
 *   C:\xampp\php\php.exe backend\tests\access_matrix.php http://localhost/optipoint/backend/api.php
 *
 * Variables d'environnement optionnelles :
 *   OPTIPOINT_BASE, OPTIPOINT_ADMIN, OPTIPOINT_ADMIN_PASS
 *
 * Première exécution : écrit backend/tests/baseline.json
 * Exécutions suivantes : compare à la baseline, affiche les écarts, code 1 si écart.
 * Les comptes TEST_* sont toujours nettoyés en fin de run.
 */
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Ce script s'exécute uniquement en CLI.\n");
    exit(1);
}

if (!function_exists('curl_init')) {
    fwrite(STDERR, "Extension PHP curl requise.\n");
    exit(1);
}

$apiUrl = getenv('OPTIPOINT_BASE');
if (!$apiUrl) {
    $apiUrl = (isset($argv[1]) && strpos($argv[1], 'http') === 0)
        ? $argv[1]
        : 'http://localhost/optipoint/backend/api.php';
}
$apiUrl = rtrim($apiUrl, '?&');
$adminMat = getenv('OPTIPOINT_ADMIN') ?: 'admin';
$adminPwd = getenv('OPTIPOINT_ADMIN_PASS') ?: 'admin123';
$baselineFile = __DIR__ . DIRECTORY_SEPARATOR . 'baseline.json';
$tmpDir = sys_get_temp_dir();

$ACTIONS = array(
    'login', 'logout', 'user', 'users', 'pointage', 'planning', 'rapport', 'stats',
    'equipes', 'equipe_membres', 'conges', 'alertes', 'messages', 'lieux',
    'parametres', 'logs', 'export', 'managers', '__unknown__'
);
$METHODS = array('GET', 'POST', 'PUT', 'DELETE');

$TEST_ACCOUNTS = array(
    'admin' => array('matricule' => 'TEST_ADM', 'nom' => 'TEST Admin', 'email' => 'test_adm@optipoint.test', 'role_id' => 1),
    'responsable' => array('matricule' => 'TEST_RESP', 'nom' => 'TEST Responsable', 'email' => 'test_resp@optipoint.test', 'role_id' => 2),
    'manager' => array('matricule' => 'TEST_MGR', 'nom' => 'TEST Manager', 'email' => 'test_mgr@optipoint.test', 'role_id' => 3),
    'employe' => array('matricule' => 'TEST_EMP', 'nom' => 'TEST Employe', 'email' => 'test_emp@optipoint.test', 'role_id' => 4),
);
$TEST_PASSWORD = 'Test1234!';

function out($msg) {
    fwrite(STDOUT, $msg . PHP_EOL);
}

function jsonShape($decoded) {
    if ($decoded === null) {
        return array('kind' => 'null');
    }
    if (!is_array($decoded)) {
        return array('kind' => gettype($decoded));
    }
    if (count($decoded) === 0) {
        return array('kind' => 'empty');
    }
    $isList = array_keys($decoded) === range(0, count($decoded) - 1);
    if ($isList) {
        $itemKeys = array();
        if (isset($decoded[0]) && is_array($decoded[0])) {
            $itemKeys = array_keys($decoded[0]);
            sort($itemKeys);
        } else {
            $itemKeys = array('_scalar_items');
        }
        return array('kind' => 'array', 'item_keys' => $itemKeys);
    }
    $keys = array_keys($decoded);
    sort($keys);
    return array('kind' => 'object', 'keys' => $keys);
}

function httpCall($apiUrl, $action, $method, $body, $cookieFile, $queryExtra) {
    $url = $apiUrl . '?action=' . rawurlencode($action);
    if ($queryExtra) {
        $url .= $queryExtra;
    }
    $ch = curl_init($url);
    $headers = array('Accept: application/json');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $raw = curl_exec($ch);
    $errno = curl_errno($ch);
    $err = curl_error($ch);
    $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($errno) {
        return array('http' => 0, 'error' => $err, 'shape' => array('kind' => 'curl_error'));
    }
    $decoded = json_decode($raw, true);
    if ($decoded === null && $raw !== '' && $raw !== 'null') {
        $snippet = preg_replace('/\s+/', ' ', substr(strip_tags($raw), 0, 180));
        return array('http' => $http, 'shape' => array('kind' => 'non_json', 'hint' => $snippet));
    }
    return array('http' => $http, 'shape' => jsonShape($decoded), 'decoded' => $decoded);
}

function snapshotCall($apiUrl, $action, $method, $body, $cookieFile, $queryExtra) {
    $r = httpCall($apiUrl, $action, $method, $body, $cookieFile, $queryExtra);
    $snap = array('http' => $r['http'], 'shape' => $r['shape']);
    if (isset($r['decoded']) && is_array($r['decoded']) && isset($r['decoded']['error'])) {
        $snap['error'] = true;
    }
    return array('snap' => $snap, 'full' => $r);
}

function newCookie($tmpDir, $name) {
    $f = $tmpDir . DIRECTORY_SEPARATOR . 'optipoint_' . $name . '_' . getmypid() . '.txt';
    if (file_exists($f)) {
        unlink($f);
    }
    touch($f);
    return $f;
}

function loginAs($apiUrl, $cookie, $matricule, $password) {
    return httpCall($apiUrl, 'login', 'POST', array(
        'matricule' => $matricule,
        'password' => $password
    ), $cookie, '');
}

function requireOk($label, $r) {
    if ($r['http'] < 200 || $r['http'] >= 300) {
        $err = '';
        if (isset($r['decoded']['error'])) {
            $err = $r['decoded']['error'];
        } elseif (isset($r['shape']['hint'])) {
            $err = $r['shape']['hint'];
        }
        throw new RuntimeException($label . ' a échoué (HTTP ' . $r['http'] . ') ' . $err);
    }
}

$cookies = array();
$createdIds = array(
    'users' => array(),
    'equipe' => null,
    'pointage' => null,
    'planning' => null,
    'conge' => null,
    'alerte' => null,
    'message' => null,
    'lieu' => null
);
$matrix = array();
$notes = array();
$exitCode = 0;

try {
    $ping = httpCall($apiUrl, '__unknown__', 'GET', null, newCookie($tmpDir, 'ping'), '');
    if ($ping['http'] === 0) {
        throw new RuntimeException(
            'API injoignable (' . $apiUrl . '). Démarrez Apache/MySQL XAMPP. ' .
            (isset($ping['error']) ? $ping['error'] : '')
        );
    }

    $adminCookie = newCookie($tmpDir, 'seed_admin');
    $cookies[] = $adminCookie;
    $adminLogin = loginAs($apiUrl, $adminCookie, $adminMat, $adminPwd);
    requireOk('Login admin seed (' . $adminMat . ')', $adminLogin);

    cleanupTestUsers($apiUrl, $adminCookie, $TEST_ACCOUNTS);

    foreach ($TEST_ACCOUNTS as $roleName => $acc) {
        $payload = array(
            'matricule' => $acc['matricule'],
            'nom' => $acc['nom'],
            'email' => $acc['email'],
            'password' => $TEST_PASSWORD,
            'service' => 'TEST',
            'horaire' => '08:00-17:00',
            'role_id' => $acc['role_id'],
            'statut' => 'actif',
            'date_embauche' => date('Y-m-d')
        );
        $r = httpCall($apiUrl, 'users', 'POST', $payload, $adminCookie, '');
        requireOk('Création ' . $acc['matricule'], $r);
        if (!isset($r['decoded']['id'])) {
            throw new RuntimeException('Création ' . $acc['matricule'] . ' : id manquant');
        }
        $createdIds['users'][$roleName] = (int) $r['decoded']['id'];
    }

    $empId = $createdIds['users']['employe'];
    $mgrId = $createdIds['users']['manager'];
    $respId = $createdIds['users']['responsable'];

    $linkMgr = httpCall($apiUrl, 'users', 'PUT', array('id' => $mgrId, 'manager_id' => $respId), $adminCookie, '');
    requireOk('Lien manager_id manager→responsable', $linkMgr);
    $linkEmp = httpCall($apiUrl, 'users', 'PUT', array('id' => $empId, 'manager_id' => $mgrId), $adminCookie, '');
    requireOk('Lien manager_id employé→manager', $linkEmp);

    $eq = httpCall($apiUrl, 'equipes', 'POST', array(
        'nom' => 'TEST_EQUIPE',
        'description' => 'Equipe de test access_matrix',
        'manager_id' => $mgrId,
        'departement' => 'TEST'
    ), $adminCookie, '');
    requireOk('Création TEST_EQUIPE', $eq);
    $createdIds['equipe'] = (int) $eq['decoded']['id'];
    $addMem = httpCall($apiUrl, 'equipe_membres', 'POST', array(
        'equipe_id' => $createdIds['equipe'],
        'utilisateur_id' => $empId,
        'role_equipe' => 'membre'
    ), $adminCookie, '');
    requireOk('Ajout TEST_EMP à TEST_EQUIPE', $addMem);

    $roleCookies = array();
    foreach ($TEST_ACCOUNTS as $roleName => $acc) {
        $c = newCookie($tmpDir, $acc['matricule']);
        $cookies[] = $c;
        $lr = loginAs($apiUrl, $c, $acc['matricule'], $TEST_PASSWORD);
        requireOk('Login ' . $acc['matricule'], $lr);
        $roleCookies[$roleName] = $c;
    }

    $anonCookie = newCookie($tmpDir, 'anon');
    $cookies[] = $anonCookie;

    $identities = array(
        'anon' => $anonCookie,
        'admin' => $roleCookies['admin'],
        'responsable' => $roleCookies['responsable'],
        'manager' => $roleCookies['manager'],
        'employe' => $roleCookies['employe']
    );

    foreach ($identities as $ident => $cookie) {
        $matrix[$ident] = array();
        foreach ($ACTIONS as $action) {
            $matrix[$ident][$action] = array();
            foreach ($METHODS as $method) {
                $body = null;
                $query = '';
                if ($method !== 'GET') {
                    $body = array();
                }
                $res = snapshotCall($apiUrl, $action, $method, $body, $cookie, $query);
                $matrix[$ident][$action][$method] = $res['snap'];
            }
        }
    }

    $matrix['_writes'] = array();

    $invalids = array(
        array('ident' => 'anon', 'action' => 'login', 'method' => 'POST', 'body' => array(), 'label' => 'login_empty'),
        array('ident' => 'anon', 'action' => 'login', 'method' => 'POST', 'body' => array('matricule' => 'x', 'password' => 'y'), 'label' => 'login_bad'),
        array('ident' => 'admin', 'action' => 'users', 'method' => 'POST', 'body' => array('nom' => 'x'), 'label' => 'users_post_incomplet'),
        array('ident' => 'admin', 'action' => 'users', 'method' => 'PUT', 'body' => array(), 'label' => 'users_put_sans_id'),
        array('ident' => 'admin', 'action' => 'users', 'method' => 'DELETE', 'body' => array(), 'label' => 'users_delete_sans_id'),
        array('ident' => 'employe', 'action' => 'pointage', 'method' => 'POST', 'body' => array('type' => 'invalide'), 'label' => 'pointage_type_invalide'),
        array('ident' => 'admin', 'action' => 'planning', 'method' => 'POST', 'body' => array('description' => 'x'), 'label' => 'planning_post_incomplet'),
        array('ident' => 'admin', 'action' => 'planning', 'method' => 'PUT', 'body' => array(), 'label' => 'planning_put_sans_id'),
        array('ident' => 'admin', 'action' => 'conges', 'method' => 'POST', 'body' => array('type_demande' => 'conge_annuel'), 'label' => 'conges_post_incomplet'),
        array('ident' => 'manager', 'action' => 'conges', 'method' => 'PUT', 'body' => array('id' => 0, 'statut' => 'nope'), 'label' => 'conges_put_statut_invalide'),
        array('ident' => 'admin', 'action' => 'equipes', 'method' => 'POST', 'body' => array(), 'label' => 'equipes_post_sans_nom'),
        array('ident' => 'admin', 'action' => 'equipe_membres', 'method' => 'POST', 'body' => array(), 'label' => 'equipe_membres_incomplet'),
        array('ident' => 'employe', 'action' => 'users', 'method' => 'POST', 'body' => array(
            'matricule' => 'HACK', 'nom' => 'H', 'email' => 'h@t.t', 'password' => 'x'
        ), 'label' => 'employe_users_post'),
        array('ident' => 'employe', 'action' => 'logs', 'method' => 'GET', 'body' => null, 'label' => 'employe_logs_get'),
        array('ident' => 'responsable', 'action' => 'users', 'method' => 'POST', 'body' => array(
            'matricule' => 'HACK2', 'nom' => 'H', 'email' => 'h2@t.t', 'password' => 'x'
        ), 'label' => 'responsable_users_post'),
        array('ident' => 'responsable', 'action' => 'parametres', 'method' => 'PUT', 'body' => array('theme' => 'dark'), 'label' => 'responsable_parametres_put'),
        array('ident' => 'manager', 'action' => 'pointage', 'method' => 'DELETE', 'body' => array('id' => 1), 'label' => 'manager_pointage_delete'),
    );

    foreach ($invalids as $w) {
        $res = snapshotCall($apiUrl, $w['action'], $w['method'], $w['body'], $identities[$w['ident']], '');
        $matrix['_writes'][$w['label']] = $res['snap'];
    }

    $today = date('Y-m-d');
    $pt = httpCall($apiUrl, 'pointage', 'POST', array(
        'type' => 'arrivee',
        'mode' => 'bureau',
        'location' => 'TEST',
        'notes' => 'TEST_MATRIX'
    ), $identities['employe'], '');
    $matrix['_writes']['employe_pointage_arrivee'] = array(
        'http' => $pt['http'],
        'shape' => $pt['shape']
    );
    if (isset($pt['decoded']['id'])) {
        $createdIds['pointage'] = (int) $pt['decoded']['id'];
        $del = snapshotCall($apiUrl, 'pointage', 'DELETE', array('id' => $createdIds['pointage']), $identities['admin'], '');
        $matrix['_writes']['admin_pointage_delete_test'] = $del['snap'];
        $createdIds['pointage'] = null;
    }

    $pl = httpCall($apiUrl, 'planning', 'POST', array(
        'utilisateur_id' => $empId,
        'date_debut' => $today,
        'date_fin' => $today,
        'description' => 'TEST_PLANNING',
        'heure_debut' => '09:00:00',
        'heure_fin' => '17:00:00'
    ), $identities['admin'], '');
    $matrix['_writes']['admin_planning_post'] = array('http' => $pl['http'], 'shape' => $pl['shape']);
    if (isset($pl['decoded']['id'])) {
        $createdIds['planning'] = (int) $pl['decoded']['id'];
        $del = snapshotCall($apiUrl, 'planning', 'DELETE', array('id' => $createdIds['planning']), $identities['admin'], '');
        $matrix['_writes']['admin_planning_delete_test'] = $del['snap'];
        $createdIds['planning'] = null;
    }

    $plEmp = snapshotCall($apiUrl, 'planning', 'POST', array(
        'utilisateur_id' => $empId,
        'date_debut' => $today,
        'date_fin' => $today,
        'description' => 'TEST_PLANNING_EMP'
    ), $identities['employe'], '');
    $matrix['_writes']['employe_planning_post'] = $plEmp['snap'];

    $cg = httpCall($apiUrl, 'conges', 'POST', array(
        'utilisateur_id' => $empId,
        'type_demande' => 'conge_annuel',
        'date_debut' => $today,
        'date_fin' => $today,
        'motif' => 'TEST'
    ), $identities['employe'], '');
    $matrix['_writes']['employe_conges_self'] = array('http' => $cg['http'], 'shape' => $cg['shape']);
    if (isset($cg['decoded']['id'])) {
        $createdIds['conge'] = (int) $cg['decoded']['id'];
    }

    $cgOther = snapshotCall($apiUrl, 'conges', 'POST', array(
        'utilisateur_id' => $mgrId,
        'type_demande' => 'conge_annuel',
        'date_debut' => $today,
        'date_fin' => $today,
        'motif' => 'TEST_FORGE'
    ), $identities['employe'], '');
    $matrix['_writes']['employe_conges_for_manager'] = $cgOther['snap'];

    $al = httpCall($apiUrl, 'alertes', 'POST', array(
        'type' => 'autre',
        'niveau' => 'faible',
        'titre' => 'TEST_ALERTE',
        'description' => 'test'
    ), $identities['admin'], '');
    $matrix['_writes']['admin_alertes_post'] = array('http' => $al['http'], 'shape' => $al['shape']);
    if (isset($al['decoded']['id'])) {
        $createdIds['alerte'] = (int) $al['decoded']['id'];
    }

    $msg = httpCall($apiUrl, 'messages', 'POST', array(
        'destinataire_id' => $mgrId,
        'sujet' => 'TEST',
        'contenu' => 'TEST'
    ), $identities['employe'], '');
    $matrix['_writes']['employe_messages_post'] = array('http' => $msg['http'], 'shape' => $msg['shape']);
    if (isset($msg['decoded']['id'])) {
        $createdIds['message'] = (int) $msg['decoded']['id'];
    }

    $lx = httpCall($apiUrl, 'lieux', 'POST', array(
        'nom' => 'TEST_LIEU',
        'adresse' => 'Test',
        'latitude' => 0.39,
        'longitude' => 9.44,
        'rayon_metres' => 50
    ), $identities['admin'], '');
    $matrix['_writes']['admin_lieux_post'] = array('http' => $lx['http'], 'shape' => $lx['shape']);
    if (isset($lx['decoded']['id'])) {
        $createdIds['lieu'] = (int) $lx['decoded']['id'];
        $del = snapshotCall($apiUrl, 'lieux', 'DELETE', array('id' => $createdIds['lieu']), $identities['admin'], '');
        $matrix['_writes']['admin_lieux_delete_test'] = $del['snap'];
        $createdIds['lieu'] = null;
    }

    $mgrPlan = snapshotCall($apiUrl, 'planning', 'GET', null, $identities['manager'], '');
    $matrix['_writes']['manager_planning_get'] = $mgrPlan['snap'];
    $mgrRap = snapshotCall($apiUrl, 'rapport', 'GET', null, $identities['manager'], '');
    $matrix['_writes']['manager_rapport_get'] = $mgrRap['snap'];
    $mgrAlert = snapshotCall($apiUrl, 'alertes', 'GET', null, $identities['manager'], '');
    $matrix['_writes']['manager_alertes_get'] = $mgrAlert['snap'];

    $payload = array(
        'generated_at' => date('c'),
        'api' => $apiUrl,
        'php' => PHP_VERSION,
        'notes' => $notes,
        'matrix' => $matrix
    );

    if (!file_exists($baselineFile)) {
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false || file_put_contents($baselineFile, $json) === false) {
            throw new RuntimeException('Impossible d\'écrire ' . $baselineFile);
        }
        out('Baseline écrite : ' . $baselineFile);
        out(countSummary($matrix));
    } else {
        $baseline = json_decode(file_get_contents($baselineFile), true);
        if (!is_array($baseline) || !isset($baseline['matrix'])) {
            throw new RuntimeException('baseline.json illisible');
        }
        $diffs = compareMatrix($baseline['matrix'], $matrix);
        if (count($diffs) === 0) {
            out('Aucun écart avec la baseline.');
        } else {
            $exitCode = 1;
            out(count($diffs) . ' écart(s) par rapport à la baseline :');
            foreach ($diffs as $d) {
                out('  - ' . $d);
            }
        }
    }
} catch (Exception $e) {
    fwrite(STDERR, 'ERREUR : ' . $e->getMessage() . PHP_EOL);
    $exitCode = 2;
}

try {
    if (isset($adminCookie) && file_exists($adminCookie)) {
        if ($createdIds['equipe']) {
            httpCall($apiUrl, 'equipes', 'DELETE', array('id' => $createdIds['equipe']), $adminCookie, '');
        }
        if ($createdIds['lieu']) {
            httpCall($apiUrl, 'lieux', 'DELETE', array('id' => $createdIds['lieu']), $adminCookie, '');
        }
        if ($createdIds['pointage']) {
            httpCall($apiUrl, 'pointage', 'DELETE', array('id' => $createdIds['pointage']), $adminCookie, '');
        }
        if ($createdIds['planning']) {
            httpCall($apiUrl, 'planning', 'DELETE', array('id' => $createdIds['planning']), $adminCookie, '');
        }
        cleanupTestUsers($apiUrl, $adminCookie, $TEST_ACCOUNTS);
        out('Comptes TEST_ nettoyés.');
    }
} catch (Exception $e) {
    fwrite(STDERR, 'Nettoyage incomplet : ' . $e->getMessage() . PHP_EOL);
    $exitCode = 2;
}

foreach ($cookies as $c) {
    if ($c && file_exists($c)) {
        @unlink($c);
    }
}

exit($exitCode);

function cleanupTestUsers($apiUrl, $adminCookie, $TEST_ACCOUNTS) {
    $equipes = httpCall($apiUrl, 'equipes', 'GET', null, $adminCookie, '');
    if ($equipes['http'] === 200 && is_array($equipes['decoded'])) {
        foreach ($equipes['decoded'] as $eq) {
            $nom = isset($eq['nom']) ? $eq['nom'] : '';
            if ($nom === 'TEST_EQUIPE' || strpos($nom, 'TEST_') === 0) {
                httpCall($apiUrl, 'equipes', 'DELETE', array('id' => $eq['id']), $adminCookie, '');
            }
        }
    }
    $list = httpCall($apiUrl, 'users', 'GET', null, $adminCookie, '&search=TEST_');
    if ($list['http'] !== 200 || !is_array($list['decoded'])) {
        return;
    }
    $wanted = array();
    foreach ($TEST_ACCOUNTS as $acc) {
        $wanted[$acc['matricule']] = true;
        $wanted[$acc['email']] = true;
    }
    foreach ($list['decoded'] as $u) {
        $mat = isset($u['matricule']) ? $u['matricule'] : '';
        $em = isset($u['email']) ? $u['email'] : '';
        if (isset($wanted[$mat]) || isset($wanted[$em]) || strpos($mat, 'TEST_') === 0) {
            httpCall($apiUrl, 'users', 'DELETE', array('id' => $u['id']), $adminCookie, '');
        }
    }
}

function countSummary($matrix) {
    $n = 0;
    foreach ($matrix as $ident => $actions) {
        if ($ident === '_writes') {
            $n += count($actions);
            continue;
        }
        foreach ($actions as $methods) {
            $n += count($methods);
        }
    }
    return $n . ' cases enregistrées.';
}

function compareMatrix($old, $new) {
    $diffs = array();
    $allIdents = array_unique(array_merge(array_keys($old), array_keys($new)));
    foreach ($allIdents as $ident) {
        if (!isset($old[$ident])) {
            $diffs[] = $ident . ' : présent maintenant, absent de la baseline';
            continue;
        }
        if (!isset($new[$ident])) {
            $diffs[] = $ident . ' : absent maintenant, présent dans la baseline';
            continue;
        }
        $oldA = $old[$ident];
        $newA = $new[$ident];
        if ($ident === '_writes') {
            $labels = array_unique(array_merge(array_keys($oldA), array_keys($newA)));
            foreach ($labels as $lab) {
                if (!isset($oldA[$lab])) {
                    $diffs[] = '_writes.' . $lab . ' : nouveau cas';
                    continue;
                }
                if (!isset($newA[$lab])) {
                    $diffs[] = '_writes.' . $lab . ' : cas disparu';
                    continue;
                }
                $d = diffSnap('_writes.' . $lab, $oldA[$lab], $newA[$lab]);
                if ($d) {
                    $diffs[] = $d;
                }
            }
            continue;
        }
        $actions = array_unique(array_merge(array_keys($oldA), array_keys($newA)));
        foreach ($actions as $action) {
            if (!isset($oldA[$action]) || !isset($newA[$action])) {
                $diffs[] = $ident . '.' . $action . ' : action ajoutée ou retirée';
                continue;
            }
            foreach (array('GET', 'POST', 'PUT', 'DELETE') as $m) {
                if (!isset($oldA[$action][$m]) || !isset($newA[$action][$m])) {
                    $diffs[] = $ident . '.' . $action . '.' . $m . ' : méthode ajoutée ou retirée';
                    continue;
                }
                $d = diffSnap($ident . '.' . $action . '.' . $m, $oldA[$action][$m], $newA[$action][$m]);
                if ($d) {
                    $diffs[] = $d;
                }
            }
        }
    }
    return $diffs;
}

function diffSnap($path, $a, $b) {
    if ((int) $a['http'] !== (int) $b['http']) {
        return $path . ' HTTP ' . $a['http'] . ' → ' . $b['http'];
    }
    $sa = isset($a['shape']) ? $a['shape'] : array();
    $sb = isset($b['shape']) ? $b['shape'] : array();
    $ka = isset($sa['kind']) ? $sa['kind'] : '';
    $kb = isset($sb['kind']) ? $sb['kind'] : '';
    if ($ka !== $kb) {
        if (($ka === 'array' && $kb === 'empty') || ($ka === 'empty' && $kb === 'array')) {
            return null;
        }
        return $path . ' shape.kind ' . $ka . ' → ' . $kb;
    }
    if (isset($sa['keys']) || isset($sb['keys'])) {
        $la = isset($sa['keys']) ? $sa['keys'] : array();
        $lb = isset($sb['keys']) ? $sb['keys'] : array();
        if ($la !== $lb) {
            return $path . ' keys [' . implode(',', $la) . '] → [' . implode(',', $lb) . ']';
        }
    }
    if (isset($sa['item_keys']) || isset($sb['item_keys'])) {
        $la = isset($sa['item_keys']) ? $sa['item_keys'] : array();
        $lb = isset($sb['item_keys']) ? $sb['item_keys'] : array();
        if ($la !== $lb && count($la) > 0 && count($lb) > 0) {
            return $path . ' item_keys [' . implode(',', $la) . '] → [' . implode(',', $lb) . ']';
        }
    }
    $ea = !empty($a['error']);
    $eb = !empty($b['error']);
    if ($ea !== $eb) {
        return $path . ' error flag ' . ($ea ? '1' : '0') . ' → ' . ($eb ? '1' : '0');
    }
    return null;
}
