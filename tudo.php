<?php
require __DIR__ . '/vendor/autoload.php';
use FreeDSx\Snmp\SnmpClient;
use FreeDSx\Snmp\Exception\SnmpRequestException;
use FreeDSx\Snmp\Exception\ConnectionException;
use FreeDSx\Snmp\Exception\UnexpectedValueException;

$client = new \Google_Client();
$client->setApplicationName('teste');
$client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
$client->setAccessType('offline');
$client->setAuthConfig(__DIR__ . '/credenciais.json');
$client->setPrompt('select_account consent');
$service = new \Google_Service_Sheets($client);
$spreadsheetId = '1eV8rbPx5wbciif63xYns6UT3_29hWmIG0ZZznZvswaw';
use Google\Service\Exception as GoogleException;

class tudo {
    public function __construct() {
        $json = json_decode(file_get_contents( "./lista.json"), true) ?? [];
        $planilhaAlterada = [];
        foreach ($json["planilhas"] as $k => $v) {
            $valueSNMP = $this->getSNMPValue($v) ?? false;
            if($valueSNMP){
                array_push($planilhaAlterada, $valueSNMP);
            }
        }

        $paginas = [];

        foreach ($planilhaAlterada as $k => $v) {
            foreach ($v["preencher"] as $kk => $vv) {
                $coluna = $vv["coluna"];
                $linha = $vv["linha"];
                $minLinha;
                $maxLinha;
                if(!isset($this->$paginas[$vv["pagina"]])){
                    $this->$paginas[$vv["pagina"]] = [];
                }
                if(!isset($this->$paginas[$vv["pagina"]][$coluna])){
                    $this->$paginas[$vv["pagina"]][$coluna] = ["itens"=>[], "minLinha"=>0, "maxLinha"=>0];
                    $minLinha = 999;
                    $maxLinha = 0;
                }
                if($vv["linha"] < $minLinha){
                    $minLinha = $vv["linha"];
                }
                if($vv["linha"] > $maxLinha){
                    $maxLinha = $vv["linha"];
                }
                $this->$paginas[$vv["pagina"]][$coluna]["minLinha"] = $minLinha;
                $this->$paginas[$vv["pagina"]][$coluna]["maxLinha"] = $maxLinha;
                array_push($this->$paginas[$vv["pagina"]][$coluna]["itens"], [$vv["text"] ?? "20450.204"]);
            }
        }

        foreach ($this->$paginas as $page => $columns) {
            foreach ($columns as $column => $index) {
               $this->dfdsgdgf($page, $column, $index["itens"], $index["minLinha"], $index["maxLinha"]);
            }
        }

    }

    function dfdsgdgf($pagina, $coluna, $values, $min, $max){
        global $client;
        global $service;
        global $spreadsheetId;
        $range = (String) $pagina . "!" . $coluna.$min. ":" .  $coluna.$max;
        $body = new \Google_Service_Sheets_ValueRange(['values' => $values]);
        $params = [
            'valueInputOption' => 'USER_ENTERED', // Pode ser 'RAW' ou 'USER_ENTERED'
        ];
        try{
            $result = $service->spreadsheets_values->update($spreadsheetId, $range, $body, $params);
            echo $result->getUpdatedCells() . " células atualizadas na pagina <b>" . $pagina. "</b> e coluna <b>" .$coluna."</b><br>";
        }catch(GoogleException $e){
            echo $pagina  . "!" .$coluna ." falhou.<br>";
        }
    }

    function getSNMPValue($data){
    $OIDs = [];
        if($data["IP"] ?? false){
            foreach ($data["preencher"] as $k => $v) {
                if($data["subOID"] || $v["OID"]){
                    $OIDs[$k] = $data["subOID"] . "." . $v["OID"];
                }
            }
            try {
                $snmp = new SnmpClient(['host' => $data["IP"],'version' => $data["version"],'community' => $data["community"], 'timeout_connect' => 1, 'timeout_read' => 1]);
                try{
                    foreach(call_user_func_array([$snmp, 'get'], $OIDs) as $k => $v) {
                        $data["preencher"][$k]["OID"] = false;
                        $data["preencher"][$k]["text"] = (String) $this->formatSNMPValue($v->getValue());
                    }
                    return $data;
                }catch (SnmpRequestException $e) {
                    echo "Erro no get";
                }catch (UnexpectedValueException $e) {
                    echo "Erro no get 2";
                }
            } catch (ConnectionException $e) {
                echo $data["descrição"]  . " falhou ao conectar.<br>";
            }
        }
    }

    function formatSNMPValue($data){
        $formatado = explode(",", $data);
        if($formatado[1]){
            $formatado = min($formatado);
        }else {
            $formatado = $formatado[0];
        }
        $formatado = explode(".", $formatado)[0];
        $formatado = number_format($formatado / 100, 2, ',', '');
        if($formatado == "-40,00" || $formatado == "-40"){
            $formatado = "DOWN";
        }
        return $formatado;
    }

    
}
