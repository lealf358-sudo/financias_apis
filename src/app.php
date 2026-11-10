
<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;

$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

$pastaDados = __DIR__ . '/../storage';

if (!is_dir($pastaDados)) {
    mkdir($pastaDados, 0777, true);
}

$arquivoCategorias = $pastaDados . '/categorias.json';
$arquivoLancamentos = $pastaDados . '/lancamentos.json';

function lerDados($arquivo, $padrao = [])
{
    if (!file_exists($arquivo)) {
        file_put_contents(
            $arquivo,
            json_encode($padrao, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        return $padrao;
    }

    $dados = json_decode(file_get_contents($arquivo), true);

    return is_array($dados) ? $dados : [];
}

function salvarDados($arquivo, $dados)
{
    file_put_contents(
        $arquivo,
        json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}

function respostaJson($response, $dados, $status = 200)
{
    $response->getBody()->write(
        json_encode($dados, JSON_UNESCAPED_UNICODE)
    );

    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus($status);
}

function proximoId($dados)
{
    if (empty($dados)) {
        return 1;
    }

    return max(array_column($dados, 'id')) + 1;
}

function dataValida($data)
{
    $dataObj = DateTime::createFromFormat('!Y-m-d', $data);

    return $dataObj !== false
        && $dataObj->format('Y-m-d') === $data;
}

function validarCategoria($dados)
{
    if (
        empty(trim($dados['nome'] ?? '')) ||
        empty($dados['tipo'] ?? '')
    ) {
        return "Nome e tipo são obrigatórios";
    }

    if (!in_array($dados['tipo'], ['receita', 'despesa'])) {
        return "Tipo deve ser receita ou despesa";
    }

    return null;
}

function validarLancamento($dados, $categorias)
{
    if (
        empty(trim($dados['descricao'] ?? '')) ||
        !isset($dados['valor']) ||
        !isset($dados['data']) ||
        empty($dados['tipo']) ||
        !isset($dados['categoria'])
    ) {
        return "Todos os campos são obrigatórios";
    }

    if (!is_numeric($dados['valor']) || $dados['valor'] <= 0) {
        return "O valor deve ser maior que zero";
    }

    if (!in_array($dados['tipo'], ['receita', 'despesa'])) {
        return "Tipo inválido";
    }

    if (!dataValida($dados['data'])) {
        return "Data inválida. Utilize AAAA-MM-DD";
    }

    $categoriaEncontrada = null;

    foreach ($categorias as $categoria) {
        if ($categoria['id'] == $dados['categoria']) {
            $categoriaEncontrada = $categoria;
            break;
        }
    }

    if (!$categoriaEncontrada) {
        return "Categoria não encontrada";
    }

    if ($categoriaEncontrada['tipo'] !== $dados['tipo']) {
        return "O tipo do lançamento deve ser compatível com a categoria";
    }

    return null;
}

$categoriasIniciais = [
    [
        "id" => 1,
        "nome" => "Salário",
        "tipo" => "receita"
    ],
    [
        "id" => 2,
        "nome" => "Moradia",
        "tipo" => "despesa"
    ]
];

$lancamentosIniciais = [
    [
        "id" => 1,
        "descricao" => "Salário",
        "valor" => 2500.00,
        "data" => "2026-11-01",
        "tipo" => "receita",
        "categoria" => 1
    ],
    [
        "id" => 2,
        "descricao" => "Conta de luz",
        "valor" => 150.00,
        "data" => "2026-11-05",
        "tipo" => "despesa",
        "categoria" => 2
    ]
];

lerDados($arquivoCategorias, $categoriasIniciais);
lerDados($arquivoLancamentos, $lancamentosIniciais);

$app->get('/status', function ($request, $response) {

    return respostaJson($response, [
        "status" => "API funcionando"
    ]);
});

$app->get('/categorias', function ($request, $response) use ($arquivoCategorias) {

    $categorias = lerDados($arquivoCategorias);

    return respostaJson($response, $categorias);
});

$app->get('/categorias/{id}', function ($request, $response, $args) use ($arquivoCategorias) {

    $categorias = lerDados($arquivoCategorias);
    $id = (int) $args['id'];

    foreach ($categorias as $categoria) {
        if ($categoria['id'] === $id) {
            return respostaJson($response, $categoria);
        }
    }

    return respostaJson($response, [
        "erro" => "Categoria não encontrada"
    ], 404);
});

$app->post('/categorias', function ($request, $response) use ($arquivoCategorias) {

    $categorias = lerDados($arquivoCategorias);
    $dados = $request->getParsedBody() ?? [];

    $erro = validarCategoria($dados);

    if ($erro) {
        return respostaJson($response, ["erro" => $erro], 422);
    }

    foreach ($categorias as $categoria) {
        if (strtolower($categoria['nome']) === strtolower(trim($dados['nome']))) {
            return respostaJson($response, [
                "erro" => "Já existe uma categoria com esse nome"
            ], 409);
        }
    }

    $novaCategoria = [
        "id" => proximoId($categorias),
        "nome" => trim($dados['nome']),
        "tipo" => $dados['tipo']
    ];

    $categorias[] = $novaCategoria;

    salvarDados($arquivoCategorias, $categorias);

    return respostaJson($response, $novaCategoria, 201);
});

$app->put('/categorias/{id}', function ($request, $response, $args) use ($arquivoCategorias, $arquivoLancamentos) {

    $categorias = lerDados($arquivoCategorias);
    $lancamentos = lerDados($arquivoLancamentos);
    $dados = $request->getParsedBody() ?? [];
    $id = (int) $args['id'];

    $erro = validarCategoria($dados);

    if ($erro) {
        return respostaJson($response, ["erro" => $erro], 422);
    }

    foreach ($categorias as $indice => $categoria) {

        if ($categoria['id'] === $id) {

            foreach ($categorias as $outra) {
                if (
                    $outra['id'] !== $id &&
                    strtolower($outra['nome']) === strtolower(trim($dados['nome']))
                ) {
                    return respostaJson($response, [
                        "erro" => "Já existe uma categoria com esse nome"
                    ], 409);
                }
            }

            foreach ($lancamentos as $lancamento) {
                if (
                    $lancamento['categoria'] === $id &&
                    $lancamento['tipo'] !== $dados['tipo']
                ) {
                    return respostaJson($response, [
                        "erro" => "Não é possível alterar o tipo: existem lançamentos incompatíveis"
                    ], 422);
                }
            }

            $categorias[$indice]['nome'] = trim($dados['nome']);
            $categorias[$indice]['tipo'] = $dados['tipo'];

            salvarDados($arquivoCategorias, $categorias);

            return respostaJson($response, $categorias[$indice]);
        }
    }

    return respostaJson($response, [
        "erro" => "Categoria não encontrada"
    ], 404);
});

$app->delete('/categorias/{id}', function ($request, $response, $args) use ($arquivoCategorias, $arquivoLancamentos) {

    $categorias = lerDados($arquivoCategorias);
    $lancamentos = lerDados($arquivoLancamentos);
    $id = (int) $args['id'];

    foreach ($lancamentos as $lancamento) {
        if ($lancamento['categoria'] === $id) {
            return respostaJson($response, [
                "erro" => "Não é possível excluir uma categoria que possui lançamentos"
            ], 409);
        }
    }

    foreach ($categorias as $indice => $categoria) {
        if ($categoria['id'] === $id) {

            unset($categorias[$indice]);
            $categorias = array_values($categorias);

            salvarDados($arquivoCategorias, $categorias);

            return respostaJson($response, [
                "mensagem" => "Categoria excluída com sucesso"
            ]);
        }
    }

    return respostaJson($response, [
        "erro" => "Categoria não encontrada"
    ], 404);
});

$app->get('/lancamentos', function ($request, $response) use ($arquivoLancamentos) {

    $lancamentos = lerDados($arquivoLancamentos);
    $filtros = $request->getQueryParams();

    $inicio = $filtros['data_inicial'] ?? null;
    $fim = $filtros['data_final'] ?? null;

    if (
        ($inicio !== null && !dataValida($inicio)) ||
        ($fim !== null && !dataValida($fim))
    ) {
        return respostaJson($response, [
            "erro" => "Período inválido"
        ], 400);
    }

    if ($inicio && $fim && $inicio > $fim) {
        return respostaJson($response, [
            "erro" => "A data inicial não pode ser posterior à data final"
        ], 400);
    }

    if (isset($filtros['tipo']) && !in_array($filtros['tipo'], ['receita', 'despesa'])) {
        return respostaJson($response, ["erro" => "Tipo inválido"], 400);
    }

    if (isset($filtros['categoria']) && filter_var($filtros['categoria'], FILTER_VALIDATE_INT) === false) {
        return respostaJson($response, ["erro" => "Categoria inválida"], 400);
    }

    $resultado = array_filter($lancamentos, function ($lancamento) use ($filtros, $inicio, $fim) {

        if ($inicio && $lancamento['data'] < $inicio) {
            return false;
        }

        if ($fim && $lancamento['data'] > $fim) {
            return false;
        }

        if (isset($filtros['tipo']) && $lancamento['tipo'] !== $filtros['tipo']) {
            return false;
        }

        if (
            isset($filtros['categoria']) &&
            $lancamento['categoria'] != $filtros['categoria']
        ) {
            return false;
        }

        return true;
    });

    return respostaJson($response, array_values($resultado));
});

$app->get('/lancamentos/{id}', function ($request, $response, $args) use ($arquivoLancamentos) {

    $lancamentos = lerDados($arquivoLancamentos);
    $id = (int) $args['id'];

    foreach ($lancamentos as $lancamento) {
        if ($lancamento['id'] === $id) {
            return respostaJson($response, $lancamento);
        }
    }

    return respostaJson($response, [
        "erro" => "Lançamento não encontrado"
    ], 404);
});

$app->post('/lancamentos', function ($request, $response) use ($arquivoLancamentos, $arquivoCategorias) {

    $lancamentos = lerDados($arquivoLancamentos);
    $categorias = lerDados($arquivoCategorias);
    $dados = $request->getParsedBody() ?? [];

    $erro = validarLancamento($dados, $categorias);

    if ($erro) {
        return respostaJson($response, ["erro" => $erro], 422);
    }

    $novoLancamento = [
        "id" => proximoId($lancamentos),
        "descricao" => trim($dados['descricao']),
        "valor" => (float) $dados['valor'],
        "data" => $dados['data'],
        "tipo" => $dados['tipo'],
        "categoria" => (int) $dados['categoria']
    ];

    $lancamentos[] = $novoLancamento;

    salvarDados($arquivoLancamentos, $lancamentos);

    return respostaJson($response, $novoLancamento, 201);
});

$app->put('/lancamentos/{id}', function ($request, $response, $args) use ($arquivoLancamentos, $arquivoCategorias) {

    $lancamentos = lerDados($arquivoLancamentos);
    $categorias = lerDados($arquivoCategorias);
    $dados = $request->getParsedBody() ?? [];
    $id = (int) $args['id'];

    $erro = validarLancamento($dados, $categorias);

    if ($erro) {
        return respostaJson($response, ["erro" => $erro], 422);
    }

    foreach ($lancamentos as $indice => $lancamento) {

        if ($lancamento['id'] === $id) {

            $lancamentos[$indice] = [
                "id" => $id,
                "descricao" => trim($dados['descricao']),
                "valor" => (float) $dados['valor'],
                "data" => $dados['data'],
                "tipo" => $dados['tipo'],
                "categoria" => (int) $dados['categoria']
            ];

            salvarDados($arquivoLancamentos, $lancamentos);

            return respostaJson($response, $lancamentos[$indice]);
        }
    }

    return respostaJson($response, [
        "erro" => "Lançamento não encontrado"
    ], 404);
});

$app->delete('/lancamentos/{id}', function ($request, $response, $args) use ($arquivoLancamentos) {

    $lancamentos = lerDados($arquivoLancamentos);
    $id = (int) $args['id'];

    foreach ($lancamentos as $indice => $lancamento) {

        if ($lancamento['id'] === $id) {

            unset($lancamentos[$indice]);
            $lancamentos = array_values($lancamentos);

            salvarDados($arquivoLancamentos, $lancamentos);

            return respostaJson($response, [
                "mensagem" => "Lançamento excluído com sucesso"
            ]);
        }
    }

    return respostaJson($response, [
        "erro" => "Lançamento não encontrado"
    ], 404);
});

function calcularResumo($lancamentos)
{
    $receitas = 0;
    $despesas = 0;

    foreach ($lancamentos as $lancamento) {

        if ($lancamento['tipo'] === 'receita') {
            $receitas += $lancamento['valor'];
        }

        if ($lancamento['tipo'] === 'despesa') {
            $despesas += $lancamento['valor'];
        }
    }

    return [
        "total_receitas" => round($receitas, 2),
        "total_despesas" => round($despesas, 2),
        "saldo" => round($receitas - $despesas, 2)
    ];
}

$app->get('/resumo', function ($request, $response) use ($arquivoLancamentos) {

    $lancamentos = lerDados($arquivoLancamentos);

    return respostaJson($response, calcularResumo($lancamentos));
});

$app->get('/resumo/periodo', function ($request, $response) use ($arquivoLancamentos) {

    $lancamentos = lerDados($arquivoLancamentos);
    $filtros = $request->getQueryParams();

    $inicio = $filtros['data_inicial'] ?? '';
    $fim = $filtros['data_final'] ?? '';

    if (!dataValida($inicio) || !dataValida($fim)) {
        return respostaJson($response, [
            "erro" => "Informe uma data inicial e final válidas"
        ], 400);
    }

    if ($inicio > $fim) {
        return respostaJson($response, [
            "erro" => "A data inicial não pode ser posterior à data final"
        ], 400);
    }

    $resultado = array_filter($lancamentos, function ($lancamento) use ($inicio, $fim) {
        return $lancamento['data'] >= $inicio &&
               $lancamento['data'] <= $fim;
    });

    $resumo = calcularResumo(array_values($resultado));

    $resumo['periodo'] = [
        "data_inicial" => $inicio,
        "data_final" => $fim
    ];

    return respostaJson($response, $resumo);
});

return $app;