<?php

namespace Tests\App\Vision;

use App\Vision\IdentityDocumentParser;
use App\Vision\ImageAnnotations;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Tests\App\Controller\ControllerTestTrait;

/**
 * @group functional
 */
class IdentityDocumentParserTest extends WebTestCase
{
    use ControllerTestTrait;

    /**
     * @var IdentityDocumentParser
     */
    private $parser;

    /**
     * @dataProvider provideCNIMatch
     */
    public function testMatchingCNI(string $text, string $firstName, string $lastName, string $birthDate): void
    {
        $birthDate = \DateTime::createFromFormat('Y-m-d', $birthDate);

        $annotations = $this->createCNIAnnotations($text);

        self::assertTrue($annotations->isIdentityDocument());
        self::assertTrue($annotations->isSupportedIdentityDocument());
        self::assertTrue($annotations->isFrenchNationalIdentityCard());

        self::assertTrue($this->parser->hasFirstName($annotations, $firstName));
        self::assertTrue($this->parser->hasLastName($annotations, $lastName));
        self::assertTrue($this->parser->hasDateOfBirth($annotations, $birthDate));
    }

    public function provideCNIMatch(): iterable
    {
        yield [<<<'TXT'
Nom: Gardien
Prénom: Rémi
Date de naissance: 27.11.1988
TXT, 'Rémi', 'Gardien', '1988-11-27'];

        yield [<<<'TXT'
CARTE NATIONALE D'IDENTITÉ N° : 12345678910
RF Nom: GARDIEN
Prénom(s): Remi Jean
Né(e) le : 27.11 1988
TXT, 'Rémi', 'Gardien', '1988-11-27'];

        yield [<<<'TXT'
CARTE NATIONALE D'IDENTITÉ N° : 12345678910
RF Nom: GARDIEN
Prénom(s): Remi, Jean
Né(e) le : 27.11 .1988
TXT, 'Rémi', 'Gardien', '1988-11-27'];
    }

    /**
     * @dataProvider providePassportMatch
     */
    public function testMatchingPassport(string $text, string $firstName, string $lastName, string $birthDate): void
    {
        $birthDate = \DateTime::createFromFormat('Y-m-d', $birthDate);

        $annotations = $this->createPassportAnnotations($text);

        self::assertTrue($annotations->isIdentityDocument());
        self::assertTrue($annotations->isSupportedIdentityDocument());
        self::assertTrue($annotations->isFrenchPassport());

        self::assertTrue($this->parser->hasFirstName($annotations, $firstName));
        self::assertTrue($this->parser->hasLastName($annotations, $lastName));
        self::assertTrue($this->parser->hasDateOfBirth($annotations, $birthDate));
    }

    public function providePassportMatch(): iterable
    {
        yield [<<<'TXT'
Page réservée aux autorités compétentes
pour déllvrer le passeport
Pagina reservada a las outoridades competentes
para expedir el pasaporte / Forbeholdt de posudstedende
myndigheder / Amtliche Vermerke
Προοριζεται για τις αρχές που είναι αρμόδιες για την
Exõoon tod čia@arpiou / Page reserved for issuing authorities
Leothanaigh in dirithe dudaráis eisiúna
Pagina riservata all'autorito
Opmerkingen van bevoegde Instondes
Pagina reservado ás entidades competentes
para emitir o passaporte / Varattu passinantoviranomaisille
Förbehållet utlömnande myndighet.
Signature du titulaire Hotels in
Ce passeport contient un composant électronique.
Il convient d'en prendre soin, et en particulier
de ne pas le plier, le perforer, l'exposer à des températures
extrêmes ou à une humidité excessive.
This passport contains sensitive electronics
for best performance please do norbeno
perforate or expose to extreme temperatures
or excess moisture
PASSEPORT
PASSPORT
RÉPUBLIQUE FRANÇAISE
Passeport
Nom/Summo
GARDIEN
Prénoms.com
Rémi, Jean
Nationaliteti
Sene58Taille 2 Couleur ces yecom correpes (3)
Francaise
M 1,78 m MARRON
Date de naissance
Lieu de naissance
27 11 1988
SEVRES
Date de dellvrang
31 07 2013
TXT, 'Rémi', 'Gardien', '1988-11-27'];
    }

    protected function setUp()
    {
        parent::setUp();

        $this->init();

        $this->parser = $this->get(IdentityDocumentParser::class);
    }

    protected function tearDown()
    {
        $this->kill();

        $this->parser = null;

        parent::tearDown();
    }

    private function createImageAnnotations(array $labels, array $webEntities, string $text): ImageAnnotations
    {
        return new ImageAnnotations($labels, $webEntities, $text);
    }

    private function createCNIAnnotations(string $text): ImageAnnotations
    {
        return $this->createImageAnnotations(
            [
                ImageAnnotations::FRENCH_IDENTITY_CARD_LABEL,
            ],
            [
                ImageAnnotations::IDENTITY_DOCUMENT_LABEL,
            ],
            $text
        );
    }

    private function createPassportAnnotations(string $text): ImageAnnotations
    {
        return $this->createImageAnnotations(
            [
                ImageAnnotations::FRENCH_PASSPORT_LABEL,
            ],
            [
                ImageAnnotations::IDENTITY_DOCUMENT_LABEL,
            ],
            $text
        );
    }
}
