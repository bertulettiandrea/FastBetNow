<?php

function getPartiteCatalog(): array
{
    return [
        [
            'squadra_casa' => 'Inter',
            'squadra_trasferta' => 'Milan',
            'campionato' => 'Serie A',
            'data' => '2026-02-05 20:45',
            'quota_casa' => 2.10,
            'quota_pareggio' => 3.40,
            'quota_trasferta' => 3.50,
        ],
        [
            'squadra_casa' => 'Barcelona',
            'squadra_trasferta' => 'Real Madrid',
            'campionato' => 'La Liga',
            'data' => '2026-02-08 21:00',
            'quota_casa' => 2.65,
            'quota_pareggio' => 3.30,
            'quota_trasferta' => 2.70,
        ],
        [
            'squadra_casa' => 'Man City',
            'squadra_trasferta' => 'Liverpool',
            'campionato' => 'Premier League',
            'data' => '2026-02-09 17:30',
            'quota_casa' => 2.20,
            'quota_pareggio' => 3.50,
            'quota_trasferta' => 3.30,
        ],
        [
            'squadra_casa' => 'Bayern',
            'squadra_trasferta' => 'Dortmund',
            'campionato' => 'Bundesliga',
            'data' => '2026-02-09 18:30',
            'quota_casa' => 1.75,
            'quota_pareggio' => 3.80,
            'quota_trasferta' => 4.50,
        ],
    ];
}

function getQuotaBySegno(array $partita, string $segno): float
{
    if ($segno === '1') {
        return (float) $partita['quota_casa'];
    }

    if ($segno === 'X') {
        return (float) $partita['quota_pareggio'];
    }

    if ($segno === '2') {
        return (float) $partita['quota_trasferta'];
    }

    throw new InvalidArgumentException('Segno non valido');
}
