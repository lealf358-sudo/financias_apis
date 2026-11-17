
<?php

use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

class FinanciasTest extends TestCase
{
    private static $app;
    private static $dadosOriginais = [];

    public static function setUpBeforeClass(): void
    {
        self::$app = require __DIR__ . '/../src/app.php';

        $pasta = __DIR__ . '/../storage';

        foreach (['categorias.json', 'lancamentos.json'] as $arquivo) {
            $caminho = $pasta . '/' . $arquivo;
            self::$dadosOriginais[$arquivo] = file_get_contents($caminho);
        }
    }

    protected function setUp(): void
    {
        $pasta = __DIR__ . '/../storage';

        foreach (self::$dadosOriginais as $arquivo => $conteudo) {
            file_put_contents($pasta . '/' . $arquivo, $conteudo);
        }
    }

    public static function tearDownAfterClass(): void
    {
        $pasta = __DIR__ . '/../storage';

        foreach (self::$dadosOriginais as $arquivo => $conteudo) {
            file_put_contents($pasta . '/' . $arquivo, $conteudo);
        }
    }

    private function requisicao($metodo, $rota, $dados = null)
    {
        $factory = new ServerRequestFactory();

        $request = $factory->createServerRequest($metodo, $rota);

        if ($dados !== null) {
            $request->getBody()->write(json_encode($dados));
            $request->getBody()->rewind();

            $request = $request->withHeader(
                'Content-Type',
                'application/json'
            );
        }

        return self::$app->handle($request);
    }

    private function respostaJson($response)
    {
        $response->getBody()->rewind();

        return json_decode(
            $response->getBody()->getContents(),
            true
        );
    }

    public function testStatusApi()
    {
        $response = $this->requisicao('GET', '/status');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotNull($this->respostaJson($response));
    }

    public function testListarCategorias()
    {
        $response = $this->requisicao('GET', '/categorias');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertIsArray($this->respostaJson($response));
    }

    public function testBuscarCategoria()
    {
        $response = $this->requisicao('GET', '/categorias/1');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotNull($this->respostaJson($response));
    }

    public function testBuscarCategoriaInexistente()
    {
        $response = $this->requisicao('GET', '/categorias/999');

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testCriarCategoria()
    {
        $dados = [
            'nome' => 'Transporte Teste',
            'tipo' => 'despesa'
        ];

        $response = $this->requisicao(
            'POST',
            '/categorias',
            $dados
        );

        $this->assertSame(201, $response->getStatusCode());
        $this->assertNotNull($this->respostaJson($response));
    }

    public function testCriarCategoriaInvalida()
    {
        $dados = [
            'nome' => '',
            'tipo' => 'despesa'
        ];

        $response = $this->requisicao(
            'POST',
            '/categorias',
            $dados
        );

        $this->assertContains(
            $response->getStatusCode(),
            [400, 422]
        );
    }

    public function testCriarCategoriaDuplicada()
    {
        $dados = [
            'nome' => 'Salário',
            'tipo' => 'receita'
        ];

        $response = $this->requisicao(
            'POST',
            '/categorias',
            $dados
        );

        $this->assertSame(409, $response->getStatusCode());
    }

    public function testEditarCategoria()
    {
        $dados = [
            'nome' => 'Salário Atualizado',
            'tipo' => 'receita'
        ];

        $response = $this->requisicao(
            'PUT',
            '/categorias/1',
            $dados
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testEditarCategoriaInexistente()
    {
        $dados = [
            'nome' => 'Categoria Nova',
            'tipo' => 'receita'
        ];

        $response = $this->requisicao(
            'PUT',
            '/categorias/999',
            $dados
        );

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testExcluirCategoriaComLancamentos()
    {
        $response = $this->requisicao('DELETE', '/categorias/1');

        $this->assertSame(409, $response->getStatusCode());
    }

    public function testExcluirCategoriaInexistente()
    {
        $response = $this->requisicao('DELETE', '/categorias/999');

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testExcluirCategoriaSemLancamentos()
    {
        $dados = [
            'nome' => 'Categoria Temporaria',
            'tipo' => 'despesa'
        ];

        $this->requisicao('POST', '/categorias', $dados);

        $lista = $this->respostaJson(
            $this->requisicao('GET', '/categorias')
        );

        $id = null;

        foreach ($lista as $categoria) {
            if ($categoria['nome'] === 'Categoria Temporaria') {
                $id = $categoria['id'];
                break;
            }
        }

        $this->assertNotNull($id);

        $response = $this->requisicao(
            'DELETE',
            '/categorias/' . $id
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testListarLancamentos()
    {
        $response = $this->requisicao('GET', '/lancamentos');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertIsArray($this->respostaJson($response));
    }

    public function testBuscarLancamento()
    {
        $response = $this->requisicao('GET', '/lancamentos/1');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotNull($this->respostaJson($response));
    }

    public function testBuscarLancamentoInexistente()
    {
        $response = $this->requisicao('GET', '/lancamentos/999');

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testCriarLancamento()
    {
        $dados = [
            'descricao' => 'Compra de teste',
            'valor' => 50.00,
            'data' => '2026-11-10',
            'tipo' => 'despesa',
            'categoria' => 2
        ];

        $response = $this->requisicao(
            'POST',
            '/lancamentos',
            $dados
        );

        $this->assertSame(201, $response->getStatusCode());
        $this->assertNotNull($this->respostaJson($response));
    }

    public function testCriarLancamentoComValorInvalido()
    {
        $dados = [
            'descricao' => 'Compra inválida',
            'valor' => 0,
            'data' => '2026-11-10',
            'tipo' => 'despesa',
            'categoria' => 2
        ];

        $response = $this->requisicao(
            'POST',
            '/lancamentos',
            $dados
        );

        $this->assertContains(
            $response->getStatusCode(),
            [400, 422]
        );
    }

    public function testCriarLancamentoComDataInvalida()
    {
        $dados = [
            'descricao' => 'Compra inválida',
            'valor' => 50,
            'data' => '2026-15-40',
            'tipo' => 'despesa',
            'categoria' => 2
        ];

        $response = $this->requisicao(
            'POST',
            '/lancamentos',
            $dados
        );

        $this->assertContains(
            $response->getStatusCode(),
            [400, 422]
        );
    }

    public function testCriarLancamentoComCategoriaInexistente()
    {
        $dados = [
            'descricao' => 'Compra inválida',
            'valor' => 50,
            'data' => '2026-11-10',
            'tipo' => 'despesa',
            'categoria' => 999
        ];

        $response = $this->requisicao(
            'POST',
            '/lancamentos',
            $dados
        );

        $this->assertContains(
            $response->getStatusCode(),
            [400, 422]
        );
    }

    public function testCriarLancamentoComTipoIncompativel()
    {
        $dados = [
            'descricao' => 'Salário inválido',
            'valor' => 1000,
            'data' => '2026-11-10',
            'tipo' => 'despesa',
            'categoria' => 1
        ];

        $response = $this->requisicao(
            'POST',
            '/lancamentos',
            $dados
        );

        $this->assertContains(
            $response->getStatusCode(),
            [400, 422]
        );
    }

    public function testEditarLancamento()
    {
        $dados = [
            'descricao' => 'Salário atualizado',
            'valor' => 3000,
            'data' => '2026-11-10',
            'tipo' => 'receita',
            'categoria' => 1
        ];

        $response = $this->requisicao(
            'PUT',
            '/lancamentos/1',
            $dados
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testEditarLancamentoInexistente()
    {
        $dados = [
            'descricao' => 'Teste',
            'valor' => 100,
            'data' => '2026-11-10',
            'tipo' => 'receita',
            'categoria' => 1
        ];

        $response = $this->requisicao(
            'PUT',
            '/lancamentos/999',
            $dados
        );

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testExcluirLancamento()
    {
        $response = $this->requisicao('DELETE', '/lancamentos/1');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testExcluirLancamentoInexistente()
    {
        $response = $this->requisicao('DELETE', '/lancamentos/999');

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testFiltrarLancamentosPorTipo()
    {
        $response = $this->requisicao(
            'GET',
            '/lancamentos?tipo=receita'
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertIsArray($this->respostaJson($response));
    }

    public function testFiltrarLancamentosPorCategoria()
    {
        $response = $this->requisicao(
            'GET',
            '/lancamentos?categoria=1'
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertIsArray($this->respostaJson($response));
    }

    public function testFiltrarLancamentosPorPeriodo()
    {
        $response = $this->requisicao(
            'GET',
            '/lancamentos?data_inicial=2026-11-01&data_final=2026-11-30'
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertIsArray($this->respostaJson($response));
    }

    public function testFiltrarLancamentosComPeriodoInvalido()
    {
        $response = $this->requisicao(
            'GET',
            '/lancamentos?data_inicial=2026-11-30&data_final=2026-11-01'
        );

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testFiltrarLancamentosComTipoInvalido()
    {
        $response = $this->requisicao(
            'GET',
            '/lancamentos?tipo=outro'
        );

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testResumoFinanceiro()
    {
        $response = $this->requisicao('GET', '/resumo');

        $this->assertSame(200, $response->getStatusCode());

        $dados = $this->respostaJson($response);

        $this->assertArrayHasKey('total_receitas', $dados);
        $this->assertArrayHasKey('total_despesas', $dados);
        $this->assertArrayHasKey('saldo', $dados);
    }

    public function testResumoPorPeriodo()
    {
        $response = $this->requisicao(
            'GET',
            '/resumo/periodo?data_inicial=2026-11-01&data_final=2026-11-30'
        );

        $this->assertSame(200, $response->getStatusCode());

        $dados = $this->respostaJson($response);

        $this->assertArrayHasKey('total_receitas', $dados);
        $this->assertArrayHasKey('total_despesas', $dados);
    }

    public function testResumoPorPeriodoSemDatas()
    {
        $response = $this->requisicao(
            'GET',
            '/resumo/periodo'
        );

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testResumoPorPeriodoComDatasInvalidas()
    {
        $response = $this->requisicao(
            'GET',
            '/resumo/periodo?data_inicial=2026-11-30&data_final=2026-11-01'
        );

        $this->assertSame(400, $response->getStatusCode());
    }
}
