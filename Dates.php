<?php

use \DateTime as Datetime;

class Dates
{
    private static array $feriados = [
        '01-01', # Ano Novo
        // ...
        '12-25', # Natal
    ];

    public static function ultimoDiaDoMes(int $ano, int $mes): int
    {
        return (int) (new DateTime("$ano-$mes-01"))->modify('last day of this month')->format('d');
    }

    public static function ajustarDiaMes(int $ano, int $mes, int $dia): DateTime
    {
        $ultimoDia = self::ultimoDiaDoMes($ano, $mes);
        $diaFinal = min($dia, $ultimoDia);

        return new DateTime(sprintf('%04d-%02d-%02d', $ano, $mes, $diaFinal));
    }

    public static function eFimDeSemana(DateTime $data): bool
    {
        $diaSemana = (int) $data->format('N');
        return $diaSemana >= 6;
    }

    public static function isFeriado(DateTime $data): bool
    {
        return in_array($data->format('m-d'), self::$feriados);
    }

    public static function eDiaUtil(DateTime $data): bool
    {
        return !self::eFimDeSemana($data) && !self::isFeriado($data);
    }

    private static function proximoDiaUtil(DateTime $data): DateTime
    {
        $nova = clone $data;

        while (!self::eDiaUtil($nova)) {
            $nova->modify('+1 day');
        }

        return $nova;
    }

    private static function diaUtilAnterior(DateTime $data): DateTime
    {
        $nova = clone $data;

        while (!self::eDiaUtil($nova)) {
            $nova->modify('-1 day');
        }

        return $nova;
    }

    public static function ajustarParaDiaUtil(DateTime $data, string $regra): DateTime
    {
        if (self::eDiaUtil($data)) {
            return clone $data;
        }

        switch ($regra) {
            case 'proximo':
                return self::proximoDiaUtil($data);

            case 'anterior':
                return self::diaUtilAnterior($data);

            case 'anterior_mesmo_mes':
                $proximo = self::diaUtilAnterior($data);

                if ($proximo->format('m') !== $data->format('m')) {
                    return self::proximoDiaUtil($data);
                }

                return $proximo;

            case 'proximo_mesmo_mes':
                $proximo = self::proximoDiaUtil($data);

                if ($proximo->format('m') !== $data->format('m')) {
                    return self::diaUtilAnterior($data);
                }

                return $proximo;

            default:
                throw new Exception("Regra inválida");
        }
    }

    public static function calcularRecorrenciaMensal(
        DateTime $dataInicio,
        int $diaOriginal,
        int $parcela
    ): DateTime {
        $mesBase = (int)$dataInicio->format('n');
        $anoBase = (int)$dataInicio->format('Y');

        $mes = $mesBase + ($parcela - 1);

        $ano = $anoBase + intdiv($mes - 1, 12);
        $mes = (($mes - 1) % 12) + 1;

        return self::ajustarDiaMes($ano, $mes, $diaOriginal);
    }

    function proximoVencimento(string $dataBase, string $frequencia): string
    {
        $map = [
            'mensal'     => 'P1M',
            'bimestral'  => 'P2M',
            'trimestral' => 'P3M',
            'semestral'  => 'P6M',
            'anual'      => 'P1Y',
        ];
    
        if (!isset($map[$frequencia])) {
            throw new InvalidArgumentException('Frequência inválida');
        }
    
        $dt = new DateTime($dataBase);
        $diaOriginal = (int)$dt->format('d');
    
        $dt->add(new DateInterval($map[$frequencia]));
    
        // Ajuste: evita pular mês (ex: 31 → vira mês seguinte)
        if ((int)$dt->format('d') !== $diaOriginal) {
            $dt->modify('last day of previous month');
        }
    
        return $dt->format('Y-m-d');
    }
}
