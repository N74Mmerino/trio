<?php

class Validador
{
    private array $datos;
    private array $errores = [];

    public function __construct(array $datos)
    {
        $this->datos = $datos;
    }

    public function requerido(string $campo, string $mensaje): self
    {
        if (trim($this->valor($campo)) === '') {
            $this->agregarError($campo, $mensaje);
        }

        return $this;
    }

    public function minimo(string $campo, int $largo, string $mensaje): self
    {
        if (mb_strlen($this->valor($campo)) < $largo) {
            $this->agregarError($campo, $mensaje);
        }

        return $this;
    }

    public function maximo(string $campo, int $largo, string $mensaje): self
    {
        if (mb_strlen($this->valor($campo)) > $largo) {
            $this->agregarError($campo, $mensaje);
        }

        return $this;
    }

    public function email(string $campo, string $mensaje): self
    {
        $valor = $this->valor($campo);

        if ($valor !== '' && filter_var($valor, FILTER_VALIDATE_EMAIL) === false) {
            $this->agregarError($campo, $mensaje);
        }

        return $this;
    }

    public function coincide(string $campo, string $otroCampo, string $mensaje): self
    {
        if ($this->valor($campo) !== $this->valor($otroCampo)) {
            $this->agregarError($campo, $mensaje);
        }

        return $this;
    }

    public function numero(string $campo, string $mensaje): self
    {
        $valor = $this->valor($campo);

        if ($valor !== '' && !is_numeric($valor)) {
            $this->agregarError($campo, $mensaje);
        }

        return $this;
    }

    public function rango(string $campo, float $minimo, float $maximo, string $mensaje): self
    {
        $valor = $this->valor($campo);

        if (is_numeric($valor) && ($valor < $minimo || $valor > $maximo)) {
            $this->agregarError($campo, $mensaje);
        }

        return $this;
    }

    public function fecha(string $campo, string $mensaje): self
    {
        $valor = $this->valor($campo);

        if ($valor !== '') {
            $fecha = DateTime::createFromFormat('!Y-m-d', $valor);

            if ($fecha === false || $fecha->format('Y-m-d') !== $valor) {
                $this->agregarError($campo, $mensaje);
            }
        }

        return $this;
    }

    public function noFutura(string $campo, string $mensaje): self
    {
        if (!$this->tieneError($campo) && $this->valor($campo) > date('Y-m-d')) {
            $this->agregarError($campo, $mensaje);
        }

        return $this;
    }

    /**
     * Política de contraseñas (Etapa 13). Única fuente de verdad:
     * la aplica PHP con passwordSegura() y la vista la publica en data-patron para registro.js.
     * Patrones sin delimitadores, con sintaxis válida en PHP (PCRE) y en JavaScript.
     */
    public const REQUISITOS_PASSWORD = [
        'largo' => ['texto' => 'Entre 8 y 72 caracteres', 'patron' => '^.{8,72}$'],
        'letra' => ['texto' => 'Al menos una letra', 'patron' => '\p{L}'],
        'numero' => ['texto' => 'Al menos un número', 'patron' => '[0-9]'],
    ];

    /** Devuelve los requisitos con 'cumple' = true/false, o null si todavía no se evaluó. */
    public static function requisitosPassword(?string $password = null): array
    {
        $requisitos = [];

        foreach (self::REQUISITOS_PASSWORD as $clave => $requisito) {
            $requisito['cumple'] = $password === null ? null : self::cumplePatron($requisito['patron'], $password);
            $requisitos[$clave] = $requisito;
        }

        return $requisitos;
    }

    public function passwordSegura(string $campo, string $mensaje): self
    {
        foreach (self::REQUISITOS_PASSWORD as $requisito) {
            if (!self::cumplePatron($requisito['patron'], $this->valor($campo))) {
                $this->agregarError($campo, $mensaje);
                break;
            }
        }

        return $this;
    }

    private static function cumplePatron(string $patron, string $valor): bool
    {
        // u: UTF-8 (cuenta caracteres, no bytes). D: "$" es el fin real de la cadena, como en JS.
        return preg_match('~' . $patron . '~uD', $valor) === 1;
    }

    public function agregarError(string $campo, string $mensaje): void
    {
        $this->errores[$campo] ??= $mensaje;
    }

    public function tieneError(string $campo): bool
    {
        return isset($this->errores[$campo]);
    }

    public function esValido(): bool
    {
        return $this->errores === [];
    }

    public function errores(): array
    {
        return $this->errores;
    }

    private function valor(string $campo): string
    {
        return (string) ($this->datos[$campo] ?? '');
    }
}
