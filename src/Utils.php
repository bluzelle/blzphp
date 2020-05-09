<?php

declare(strict_types=1);

namespace Bluzelle;

use \BitWasp\Bitcoin\Mnemonic\Bip39\Bip39SeedGenerator;
use \BitWasp\Bitcoin\Mnemonic\MnemonicFactory;
use \BitWasp\Bitcoin\Key\Factory\HierarchicalKeyFactory;
use \BitWasp\Bech32;

class Utils
{
    public static function getAddressFromPublicKey($publicKey)
    {
        $hexKey = self::decodeHex($publicKey);
        
        $hash = self::sha256Digest($hexKey, true);
        $hash = self::rmd160Digest($hash, true);
        
        $arr = \unpack('C*', $hash);
        
        $word = Bech32\convertBits(self::offsetArray($arr), count($arr), 8, 5, false);
        return Bech32\encode('bluzelle', $word);
    }

    public static function offsetArray($arr)
    {
        $res = [];

        for ($i = 0; $i<count($arr); $i++) {
            $res[$i] = $arr[$i + 1];
        }

        return $res;
    }

    public static function convertLease($lease)
    {
        if (isset($lease)) {
            return 0;
        }

        $seconds = 0;

        $seconds += isset($lease['days']) ? ((int) $lease['days']) * 24 * 60 * 60 : 0;
        $seconds += isset($lease['hours']) ? ((int) $lease['hours']) * 60 * 60 : 0;
        $seconds += isset($lease['minutes']) ? ((int) $lease['minutes']) * 60 : 0;
        $seconds += isset($lease['seconds']) ? (int) $lease['seconds'] : 0;

        return $seconds;
    }

    public static function jsonDecode($obj)
    {
        return \json_decode($obj, true);
    }

    public static function decodeHex($hex)
    {
        return \hex2bin($hex);
    }

    public static function sha256Digest($str, $raw = false)
    {
        return \openssl_digest($str, 'sha256', $raw);
    }

    public static function rmd160Digest($str, $raw = false)
    {
        return \openssl_digest($str, 'ripemd160', $raw);
    }

    public static function base64Encode($obj)
    {
        return \base64_encode($obj);
    }

    public static function jsonEncode($obj)
    {
        return \json_encode($obj, JSON_UNESCAPED_SLASHES);
    }

    public static function makeRandomString()
    {
        $randomStr = '';
        $chars = \str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789');

        for ($i = 0; $i < 32; $i++) {
            $randomStr .= $chars[array_rand($chars, 1)];
        }

        return $randomStr;
    }

    public static function ksortRecursive(&$array, $sort_flags = SORT_REGULAR)
    {
        if (!is_array($array)) {
            return false;
        }
        
        ksort($array, $sort_flags);
        
        foreach ($array as &$arr) {
            self::ksortRecursive($arr, $sort_flags);
        }
        
        return true;
    }

    public static function getECKey($mnemonic)
    {
        $seed = self::seedFromMnemonic($mnemonic);
        $master = self::bip32FromSeed($seed);
        return $master->derivePath("44'/118'/0'/0/0");
    }

    public static function seedFromMnemonic($mnemonic)
    {
        $bip39 = MnemonicFactory::bip39();
        $seedGenerator = new Bip39SeedGenerator();
        return $seedGenerator->getSeed($mnemonic, '');
    }

    public static function bip32FromSeed($seed)
    {
        $hdFactory = new HierarchicalKeyFactory();
        return $hdFactory->fromEntropy($seed);
    }


    public static function substring($str, $start, $end = null)
    {
        return isset($end) ? substr($str, $start, $end - $start) : substr($str, $start);
    }
}
