<?php

namespace Tests\Unit;

use App\Services\NocnokPropertyPageFetcher;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NocnokPropertyPageFetcherTest extends TestCase
{
    private const GALLERY_IMAGE = 'https://s3.amazonaws.com/nocnok-img/pp-260511065709-04967164a6304f4591767347d0726ebel.webp';

    private const OG_IMAGE = 'https://s3.amazonaws.com/nocnok-img/pp-260511065709-04967164a6304f4591767347d0726ebem.webp';

    private const PROPERTY_SNIPPET = 'constructionSize\":300,\"lotSize\":270,\"bedrooms\":3,\"agentName\":\"Gabriela Guerra\",\"agentEmail\":\"gabriela@rentas.com\"';

    private NocnokPropertyPageFetcher $fetcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fetcher = new NocnokPropertyPageFetcher;
    }

    #[Test]
    public function extrae_la_primera_imagen_de_picture_urls(): void
    {
        $html = '"pictureUrls":["'.self::GALLERY_IMAGE.'"]';

        $this->assertSame(self::GALLERY_IMAGE, $this->fetcher->extractCoverImageFromHtml($html));
    }

    #[Test]
    public function usa_og_image_si_no_hay_picture_urls(): void
    {
        $html = '<meta property="og:image" content="'.self::OG_IMAGE.'"/>';

        $this->assertSame(self::OG_IMAGE, $this->fetcher->extractCoverImageFromHtml($html));
    }

    #[Test]
    public function extrae_metros_recamaras_y_agente_desde_json_de_next_js(): void
    {
        $parsed = $this->fetcher->parseFromHtml(self::PROPERTY_SNIPPET);

        $this->assertSame('300', $parsed['metros_cuadrados']);
        $this->assertSame(3, $parsed['numero_recamaras']);
        $this->assertSame('gabriela@rentas.com', $parsed['agent_email']);
        $this->assertSame('Gabriela Guerra', $parsed['agent_name']);
    }

    #[Test]
    public function parsea_todos_los_campos_juntos(): void
    {
        $html = 'pictureUrls\\":[\\"'.self::GALLERY_IMAGE.'\\",'.self::PROPERTY_SNIPPET;

        $parsed = $this->fetcher->parseFromHtml($html);

        $this->assertSame(self::GALLERY_IMAGE, $parsed['imagen_propiedad']);
        $this->assertSame('300', $parsed['metros_cuadrados']);
        $this->assertSame(3, $parsed['numero_recamaras']);
        $this->assertSame('gabriela@rentas.com', $parsed['agent_email']);
    }
}
