<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Smalot\PdfParser\Parser;

class IAService {

    public function analizarDocumento($rutaArchivo) {

        // 1. Extraer texto
        $parser = new Parser();
        $pdf = $parser->parseFile($rutaArchivo);
        $texto = substr($pdf->getText(), 0, 4000);

        // 2. Preparar prompt
$prompt = "
Analiza el siguiente documento de seguridad social en Colombia.

Responde SOLO en JSON válido:

{
  \"tipo_documento\": \"planilla | certificado_afiliacion | desprendible_pago | desconocido\",
  \"nit_pagador\": \"solo números o null\",
  \"nombre_empresa\": \"string o null\",
  \"cc_conductor\": \"solo números o null\",
  \"fecha_afiliacion\": \"YYYY-MM-DD o null\"
}

INSTRUCCIONES:

1. nit_pagador:
- Buscar palabras como: NIT, NI, N.I.T
- Extraer solo números (sin puntos ni guiones)

2. nombre_empresa:
- Es el nombre de la empresa que paga
- Suele estar cerca del NIT

3. cc_conductor:
- Buscar CC, cédula, documento

4. fecha_afiliacion:
- Si hay periodo → usar la fecha más reciente
- Si hay meses → usar último mes YYYY-MM-01

REGLAS:
- Si no estás seguro → null
- NO inventar datos
- SOLO JSON

TEXTO:
$texto
";

        // 3. Llamada a OpenAI (curl)
     $apiKey = getenv('OPENAI_API_KEY');

if (!$apiKey) {
    return ["error" => "API KEY no configurada"];
}

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/chat/completions");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer $apiKey"
        ]);

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            "model" => "gpt-4.1",
            "messages" => [
                ["role" => "system", "content" => "Eres un analizador de documentos."],
                ["role" => "user", "content" => $prompt]
            ],
            "temperature" => 0
        ]));

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        $contenido = $data['choices'][0]['message']['content'] ?? '{}';

        // 4. Convertir respuesta a array
        $resultado = json_decode($contenido, true);

        return $resultado ?? ["tipo_documento" => "error"];
    }
}
?>