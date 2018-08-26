<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 8/25/18
 * Time: 9:37 PM
 */

function getMAC($ip)
{
    if($ip == '127.0.0.1')
    {
        return "d0:df:9a:c4:07:ab";
    }
    else
    {
        do
        {
            $arp_scan = shell_exec("arp-scan " . $ip); //necessario executar como root
            $linhas = explode("\n", $arp_scan);
            $array = str_split($linhas[2]);
            $mac = '';
            $i = 13;
            while($i < strlen($linhas[2]) && $i <=29)
            {
                $mac = $mac . $array[$i];
                $i++;
            }
        }while(strlen($mac) < 17);
        return $mac;
    }

}

function macParaBinario($mac)
{
    $binario = '';
    $macArray = explode(':', $mac);
    foreach ($macArray as $hexaComDoisDigitos)
    {
        $bin =  base_convert($hexaComDoisDigitos, 16, 2);
        while( strlen($bin) < 8)
        {
            $bin = '0'. $bin;
        }
        $binario = $binario . $bin;
    }
    return $binario;
}

function binarioParaMac($binario)
{
    $macDesformatado =  base_convert($binario, 2, 16);
    $mac = substr($macDesformatado, 0, 2);
    for ($i = 2; $i < strlen($macDesformatado); $i += 2)
    {
        $mac = $mac . ":" . substr($macDesformatado, $i, 2);
    }
    return $mac;
}

function enviarMessagemServidor($socket, $mensagem)
{
    echo "Message To server :".$mensagem;
// send string to server
    socket_write($socket, $mensagem, strlen($mensagem)) or die("Could not send data to server\n");
}

function receberRespostaServidor($socket, $limiteMensagem)
{
    $result = socket_read ($socket, $limiteMensagem) or die("Could not read server response\n");
    echo " Reply From Server  :".$result . "\n";
    return $result;
}

function conectarAoServidor($host, $port, $mensagem, $limite)
{
    $socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
    $result = socket_connect($socket, $host, $port) or die("Could not connect to server\n");
    enviarMessagemServidor($socket, $mensagem);
    $resposta = receberRespostaServidor($socket, $limite);
    socket_close($socket);
    return $resposta;
}
function string_to_bin($string){ //converte a string em uma sequencia binaria//
    $bin = '';
    $chars = str_split($string); //separa a string em um array de caracteres//
    foreach($chars as $c){//para cada caractere na string//
        $hex = unpack('H*', $c);//passa o caractere para hexadecimal//
        $b = base_convert($hex[1], 16, 2); //passa o hexadecimal para binario//
        while(strlen($b)<8){ $b = '0'.$b; } //garante que tem 8 bits//
        $bin .= $b; //concatena//
    } //echo "String: ".$string."</br>Binario: ".$bin."</br>";//debug//
    return $bin;
}

function getMensagemPacote()
{
    $conteudo = file('../pacote.txt');
    $split = explode(' ', $conteudo[0]);
    return $split[1];
}
function getIpPacote()
{
    $conteudo = file('../pacote.txt');
    $split = explode(' ', $conteudo[0]);
    return $split[0];
}
function MontaQuadro()
{
    $ipDestino = getIpPacote();
    $mensagem = getMensagemPacote();
    $ipOrigem = file('../myIp.txt');
    $ipOrigem = $ipOrigem[0];

    $preambulo = '0101';
    $sfd = '10101011'; // Delimitador de início de quadro //
    $macOrigem = macParaBinario(getMAC($ipOrigem));
    $macDestino = macParaBinario(getMAC($ipDestino));
    $tipo = '0100100101010000';//IP//
    $data = string_to_bin($mensagem); //converte o pacote para binário//
    $crc = '01000101010100100101001001001111'; //string ERRO//
    return $preambulo.$sfd.$macOrigem.$macDestino.$tipo.$data.$crc;
}

function bin_to_string($bin){ //converte a sequencia binaria para uma string//
    $string = '';
    for($i=0; $i<(strlen($bin)-1); $i+=8){//para cada caractere em binário//
        $hex = base_convert(substr($bin, $i, 8), 2, 16);//converte de binário para hexadecimal//
        while(strlen($hex)<2){ $hex = '0'.$hex; }
        $c = pack('H*', $hex);//passa o hexadecimal para caractere, concatena na string//
        $string .= $c;
    } //echo "Binario: ".$bin."<</br>>String: ".$string."<</br>>";//debug//
    return $string;
}
//=========================
$host    = "127.0.0.1";
$port    = 8080;

$quadro = MontaQuadro();
print $quadro ."\n";

$tamMensagemEmBinario = conectarAoServidor($host,$port,string_to_bin("TAM"), 1024);
$tamMensagem = bin_to_string($tamMensagemEmBinario);
$mensagem = MontaQuadro();
$resposta = conectarAoServidor($host,$port, $mensagem, $tamMensagem);
if(strcmp($resposta, $mensagem) == 0)
{
    print "Pacote recebido com sucesso!";
}
