<?php

namespace Tests\Unit\Support;

use App\Support\ContenuHtml;
use PHPUnit\Framework\TestCase;

class ContenuHtmlTest extends TestCase
{
    public function test_it_removes_scripts_attributes_and_unknown_tags(): void
    {
        ['html' => $html] = ContenuHtml::preparer(
            '<p onclick="vol()">Texte <a href="javascript:vol()">lien</a><img src=x onerror=vol()></p>'
            .'<script>vol()</script><style>p{}</style><!-- note --><iframe src="//x"></iframe>'
        );

        $this->assertSame('<p>Texte lien</p>', $html);
    }

    public function test_it_keeps_formatting_tags_accents_and_table_scope(): void
    {
        ['html' => $html] = ContenuHtml::preparer('<table><tbody><tr><th scope="row" class="x">Échelle</th><td>1/18</td></tr></tbody></table>');

        $this->assertSame('<table><tbody><tr><th scope="row">Échelle</th><td>1/18</td></tr></tbody></table>', $html);
    }

    public function test_it_builds_a_table_of_contents_with_unique_anchors(): void
    {
        $resultat = ContenuHtml::preparer('<h2>Étapes</h2><p>a</p><h2>Étapes</h2>');

        $this->assertSame([['id' => 'etapes', 'titre' => 'Étapes'], ['id' => 'etapes-2', 'titre' => 'Étapes']], $resultat['sommaire']);
        $this->assertStringContainsString('<h2 id="etapes-2">', $resultat['html']);
    }
}
