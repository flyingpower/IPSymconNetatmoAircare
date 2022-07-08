<?php

declare(strict_types=1);

trait NetatmoAircareLocalLib
{
    public static $IS_NODATA = IS_EBASE + 10;
    public static $IS_UNAUTHORIZED = IS_EBASE + 11;
    public static $IS_FORBIDDEN = IS_EBASE + 12;
    public static $IS_SERVERERROR = IS_EBASE + 13;
    public static $IS_HTTPERROR = IS_EBASE + 14;
    public static $IS_INVALIDDATA = IS_EBASE + 15;
    public static $IS_NOPRODUCT = IS_EBASE + 16;
    public static $IS_PRODUCTMISSІNG = IS_EBASE + 17;
    public static $IS_NOLOGIN = IS_EBASE + 18;

    private function GetFormStatus()
    {
        $formStatus = $this->GetCommonFormStatus();

        $formStatus[] = ['code' => self::$IS_NODATA, 'icon' => 'error', 'caption' => 'Instance is inactive (no data)'];
        $formStatus[] = ['code' => self::$IS_UNAUTHORIZED, 'icon' => 'error', 'caption' => 'Instance is inactive (unauthorized)'];
        $formStatus[] = ['code' => self::$IS_FORBIDDEN, 'icon' => 'error', 'caption' => 'Instance is inactive (forbidden)'];
        $formStatus[] = ['code' => self::$IS_SERVERERROR, 'icon' => 'error', 'caption' => 'Instance is inactive (server error)'];
        $formStatus[] = ['code' => self::$IS_HTTPERROR, 'icon' => 'error', 'caption' => 'Instance is inactive (http error)'];
        $formStatus[] = ['code' => self::$IS_INVALIDDATA, 'icon' => 'error', 'caption' => 'Instance is inactive (invalid data)'];
        $formStatus[] = ['code' => self::$IS_NOPRODUCT, 'icon' => 'error', 'caption' => 'Instance is inactive (no product)'];
        $formStatus[] = ['code' => self::$IS_PRODUCTMISSІNG, 'icon' => 'error', 'caption' => 'Instance is inactive (product missing)'];
        $formStatus[] = ['code' => self::$IS_NOLOGIN, 'icon' => 'error', 'caption' => 'Instance is inactive (not logged in)'];

        return $formStatus;
    }

    public static $STATUS_INVALID = 0;
    public static $STATUS_VALID = 1;
    public static $STATUS_RETRYABLE = 2;

    private function CheckStatus()
    {
        switch ($this->GetStatus()) {
            case IS_ACTIVE:
                $class = self::$STATUS_VALID;
                break;
            case self::$IS_NODATA:
            case self::$IS_UNAUTHORIZED:
            case self::$IS_FORBIDDEN:
            case self::$IS_SERVERERROR:
            case self::$IS_HTTPERROR:
            case self::$IS_INVALIDDATA:
                $class = self::$STATUS_RETRYABLE;
                break;
            default:
                $class = self::$STATUS_INVALID;
                break;
        }

        return $class;
    }

    public static $CONNECTION_UNDEFINED = 0;
    public static $CONNECTION_OAUTH = 1;
    public static $CONNECTION_DEVELOPER = 2;

    public static $WIFI_LOW = 0;
    public static $WIFI_AVERAGE = 1;
    public static $WIFI_HIGH = 2;

    public static $INDEX_HEALTY = 0;
    public static $INDEX_FINE = 1;
    public static $INDEX_FAIR = 2;
    public static $INDEX_POOR = 3;
    public static $INDEX_UNHEALTY = 4;

    private function InstallVarProfiles(bool $reInstall = false)
    {
        if ($reInstall) {
            $this->SendDebug(__FUNCTION__, 'reInstall=' . $this->bool2str($reInstall), 0);
        }

        $associations = [
            ['Wert' => self::$INDEX_HEALTY, 'Name' => $this->Translate('Healthy'), 'Farbe' => 0x88A4C9],
            ['Wert' => self::$INDEX_FINE, 'Name' => $this->Translate('Fine'), 'Farbe' => 0x68C29F],
            ['Wert' => self::$INDEX_FAIR, 'Name' => $this->Translate('Fair'), 'Farbe' => 0xF5E552],
            ['Wert' => self::$INDEX_POOR, 'Name' => $this->Translate('Poor'), 'Farbe' => 0xF6C57C],
            ['Wert' => self::$INDEX_UNHEALTY, 'Name' => $this->Translate('Unhealthy'), 'Farbe' => 0xED7071]
        ];
        $this->CreateVarProfile('NetatmoAircare.Index', VARIABLETYPE_INTEGER, '', 0, 0, 0, 1, '', $associations, $reInstall);

        $associations = [
            ['Wert' =>  0, 'Name' => '%d', 'Farbe' => 0x88A4C9],
            ['Wert' => 56, 'Name' => '%d', 'Farbe' => 0x68C29F],
            ['Wert' => 65, 'Name' => '%d', 'Farbe' => 0xF5E552],
            ['Wert' => 70, 'Name' => '%d', 'Farbe' => 0xF6C57C],
            ['Wert' => 80, 'Name' => '%d', 'Farbe' => 0xED7071],
        ];
        $this->CreateVarProfile('NetatmoAircare.Noise', VARIABLETYPE_INTEGER, ' dB', 0, 130, 0, 1, 'Speaker', $associations, $reInstall);

        $associations = [
            ['Wert' =>    0, 'Name' => '%d', 'Farbe' => 0x88A4C9],
            ['Wert' => 900, 'Name' => '%d', 'Farbe' => 0x68C29F],
            ['Wert' => 1150, 'Name' => '%d', 'Farbe' => 0xF5E552],
            ['Wert' => 1400, 'Name' => '%d', 'Farbe' => 0xF6C57C],
            ['Wert' => 1600, 'Name' => '%d', 'Farbe' => 0xED7071],
        ];
        $this->CreateVarProfile('NetatmoAircare.CO2', VARIABLETYPE_INTEGER, ' ppm', 250, 2000, 0, 1, 'Gauge', $associations, $reInstall);

        $associations = [
            ['Wert' => self::$WIFI_LOW, 'Name' => $this->Translate('low'), 'Farbe' => 0xEE0000],
            ['Wert' => self::$WIFI_AVERAGE, 'Name' => $this->Translate('average'), 'Farbe' => 0xFFFF00],
            ['Wert' => self::$WIFI_HIGH, 'Name' => $this->Translate('high'), 'Farbe' => 0x32CD32],
        ];
        $this->CreateVarProfile('NetatmoAircare.WifiStrength', VARIABLETYPE_INTEGER, '', 0, 0, 0, 1, 'Intensity', $associations, $reInstall);

        $this->CreateVarProfile('NetatmoAircare.Temperatur', VARIABLETYPE_FLOAT, ' °C', -10, 30, 0, 1, 'Temperature', [], $reInstall);
        $this->CreateVarProfile('NetatmoAircare.Pressure', VARIABLETYPE_FLOAT, ' mbar', 500, 1200, 0, 0, 'Gauge', [], $reInstall);
        $this->CreateVarProfile('NetatmoAircare.Humidity', VARIABLETYPE_FLOAT, ' %', 0, 0, 0, 0, 'Drops', [], $reInstall);
        $this->CreateVarProfile('NetatmoAircare.absHumidity', VARIABLETYPE_FLOAT, ' g/m³', 10, 100, 0, 0, 'Drops', [], $reInstall);
        $this->CreateVarProfile('NetatmoAircare.Dewpoint', VARIABLETYPE_FLOAT, ' °C', 0, 30, 0, 0, 'Drops', [], $reInstall);
        $this->CreateVarProfile('NetatmoAircare.Heatindex', VARIABLETYPE_FLOAT, ' °C', 0, 100, 0, 0, 'Temperature', [], $reInstall);
    }
}
