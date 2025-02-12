<?php
    /* Abeille plugin has splitted code for
       - list of equipements (Abeille.php)
       - equipement detail (main/advanced/commands => AbeilleEQ)
       For this reason redirection is required to 'AbeilleEQ' if equipment detail is requested */
    if (isset($_GET['id']) && is_numeric($_GET['id'])) { // If 'id' is set to number, let's redirect to 'AbeilleEq' page
        $uri = parse_url($_SERVER['REQUEST_URI']);
        // Replace "p=Abeille" by "p=AbeilleEq"
        $newuri = str_replace("p=Abeille", "p=AbeilleEq", $uri['query']);
        // $newuri = str_replace("&ajax=1", "", $newuri); // Required since core v4.1
        header("Location: index.php?".$newuri);
        exit;
    }

    /*
     * The following part is executed only if no equipment selected (no id)
     */

    /* Developers debug features & PHP errors */
    require_once __DIR__.'/../../core/config/Abeille.config.php';
    if (file_exists(dbgFile)) {
        $dbgDeveloperMode = true;
        $dbgConfig = json_decode(file_get_contents(dbgFile), true);
        if (isset($dbgConfig["defines"])) {
            $arr = $dbgConfig["defines"];
            foreach ($arr as $idx => $value) {
                if ($value == "Tcharp38")
                    $dbgTcharp38 = true;
            }
        }
        echo '<script>var js_dbgDeveloperMode = '.$dbgDeveloperMode.';</script>'; // PHP to JS
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    sendVarToJS('eqType', 'Abeille');
    $abQueues = $GLOBALS['abQueues'];
    echo '<script>var js_queueXToParser = "'.$abQueues['xToParser']['id'].'";</script>'; // PHP to JS
    echo '<script>var js_queueXToCmd = "'.$abQueues['xToCmd']['id'].'";</script>'; // PHP to JS

    $eqLogics = eqLogic::byType('Abeille');
    /* Creating a per Zigate list of eq ids.
       For each zigate, the first eq is the zigate.
       $eqPerZigate[zgId][0] => id for zigate
       $eqPerZigate[zgId][1] => id for next eq... */
    $eqPerZigate = array(); // All equipements id/addr per zigate
    foreach ($eqLogics as $eqLogic) {
        $eqLogicId = $eqLogic->getLogicalId(); // Ex: 'Abeille1/0000'
        list($eqNet, $eqAddr) = explode( "/", $eqLogicId);
        $zgId = hexdec(substr($eqNet, 7)); // Extracting zigate number from network
        $eqId = $eqLogic->getId();
        $eq = [];
        $eq['id'] = $eqId;
        $eq['addr'] = $eqAddr;
        $eq['mainEp'] = $eqLogic->getConfiguration('mainEP', '');
        $eqModel = $eqLogic->getConfiguration('ab::eqModel', null);
        $eq['jsonId'] = $eqModel ? $eqModel['id'] : '';
        if ($eqAddr == "0000") {
            if (isset($eqPerZigate[$zgId][$eqId]))
                array_unshift($eqPerZigate[$zgId][$eqId], $eq);
            else
                $eqPerZigate[$zgId][$eqId] = $eq;
        } else
            $eqPerZigate[$zgId][$eqId] = $eq;
    }
    echo '<script>var js_eqPerZigate = \''.json_encode($eqPerZigate).'\';</script>';

    // logDebug("eqPerZigate=".json_encode($eqPerZigate)); // In dev mode only
    // $parametersAbeille = AbeilleTools::getParameters();

    $outils = array(
        'health'    => array( 'bouton'=>'bt_healthAbeille',         'icon'=>'fa-medkit',        'text'=>'{{Santé}}' ),
        'netList'   => array( 'bouton'=>'bt_network',               'icon'=>'fa-sitemap',       'text'=>'{{Réseau}}' ),
        'net'       => array( 'bouton'=>'bt_networkAbeille',        'icon'=>'fa-map',           'text'=>'{{Network Graph}}' ),
        'compat'    => array( 'bouton'=>'bt_supportedEqList',       'icon'=>'fa-align-left',    'text'=>'{{Compatibilite}}' ),
        'ota'       => array( 'bouton'=>'bt_Ota',                   'icon'=>'fa-paperclip',     'text'=>'{{Mises-à-jour OTA}}' ),
        'support'   => array( 'bouton'=>'bt_supportPage',           'icon'=>'fa-medkit',        'text'=>'{{Support}}' ),
    );
    if (isset($dbgDeveloperMode))
        $outils['graph'] = array( 'bouton'=>'bt_graph', 'icon'=>'fa-flask', 'text'=>'{{Graph}}' );
?>

<!-- For all modals on 'Abeille' page. -->
<div class="row row-overflow" id="abeilleModal">
</div>

<div class="row row-overflow">
	<form action="plugins/Abeille/desktop/php/AbeilleFormAction.php" method="post">

        <!-- Barre d outils horizontale  -->
		<div class="col-xs-12 eqLogicThumbnailDisplay">

        <!-- Icones de toutes les modales  -->
        <?php include '010_AbeilleGestionPart.php'; ?>

        <!-- Icones de toutes les abeilles  -->
        <?php include '020_AbeilleMesAbeillesPart.php'; ?>

        <!-- Groups management  -->
        <?php include 'Abeille-Groups.php'; ?>

        <!-- Replace equipment on Jeedom side -->
        <?php include 'Abeille-ReplaceEq.php'; ?>

        <!-- Gestion des ReHome / migration d equipements  -->
        <?php include 'Abeille-MigrateEq.php'; ?>

        <?php if (isset($dbgTcharp38)) { ?>
        <?php include 'Abeille-Recovery.php'; ?>
        <?php } ?>

        <?php include 'Abeille-NewZigate.php'; ?>

        <?php if (isset($dbgDeveloperMode)) { ?>
        <legend><i class="fa fa-cogs"></i> {{Visible en MODE DEV UNIQUEMENT}}</legend>
        <div class="form-group" style="background-color: rgba(var(--defaultBkg-color), var(--opacity)) !important; padding-left: 10px">

            <!-- Gestion des scenes  -->
            <?php include 'Abeille-Scenes.php'; ?>

        </div>
        <?php } ?>

    </div> <!-- Fin - Barre d outils horizontale  -->

	</form>
</div>

<!-- Scripts -->
<?php include 'Abeille-Js.php'; ?>
