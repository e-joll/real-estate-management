<?php

namespace App\Service;

use DOMDocument;
use DOMElement;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailService
{
    public function __construct(private readonly MailerInterface $mailer)
    {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendEmail(string $to, string $subject, string $content): void
    {
        $htmlContent = $this->generateAppropriateHtmlContent($content);

        $email = (new Email())
            ->from('noreply@example.com')
            ->to($to)
            ->subject($subject)
            ->html($htmlContent);

        $this->mailer->send($email);
    }

    public function generateAppropriateHtmlContent(string $content): string
    {
        $crawlerEmail = new Crawler();
        $crawlerEmail->add(
            '<html><head><meta name="viewport" content="width=device-width, initial-scale=1"><style>
            body {
                margin: 0;
                padding: 0;
                -webkit-text-size-adjust: 100%; 
                -ms-text-size-adjust: 100%; 
            }
            table {
                border-collapse: collapse;
                width: 100%;
            }</style></head><body><table><tbody></tbody></table></body></html>'
        );

        // recup tbody
        $tbody = $crawlerEmail->filter('tbody')->getNode(0);

        // Add body
        $content = '<body>' . $content . '</body>';
        $crawlerContent = new Crawler($content);


        // Récupérer et afficher toutes les balises à l'intérieur de <body>
        $crawlerContent->filter('body > *')->each(function (Crawler $node) use ($crawlerContent, $tbody) {
            $newRows = [];
            $padding = 0;
            if ($node->nodeName() === 'div') {
                $this->moveBrAfterTags($crawlerContent);
                $paragraphs = explode('<br>', $node->html());
                foreach ($paragraphs as $n => $p) {
                    if (empty(trim($p))) {
                        $padding += 10;
                    } elseif (empty($newRows)) {
                        $newRows[] = '<tr><td><p style="padding-top: ' . $padding . 'px;">' . $p . '</p></td></tr>';
                        $padding = 10;
                    } else {
                        $newRows[] = '<tr><td><p>' . $p . '</p></td></tr>';
                        $newRows[count($newRows) - 1] = str_replace('<p>', '<p style="padding-top: ' . $padding . 'px;">', end($newRows));
                        $padding = 10;
                    }
                }
                if ($padding > 0) {
                    $newRows[count($newRows) - 1] = str_replace('<p>', '<p style="padding-top: '.$padding.'px;">',end($newRows));
                }
            } else {
                $newRows[] = '<tr><td>' . $node->outerHtml() . '</td></tr>';
            }
            // TODO: encodage
            foreach ($newRows as $row) {
                $newRoWDomDocument = new DOMDocument();
                @$newRoWDomDocument->loadHTML($row);
                $newRow = $newRoWDomDocument->getElementsByTagName('tr')->item(0);
                $importedRow = $tbody->ownerDocument->importNode($newRow, true);

                $tbody->appendChild($importedRow);
            }
        });

        return $crawlerEmail->outerHtml();
    }

    private function processNode($node, &$html): void
    {

    }

    function moveBrAfterTags(Crawler $crawler): void
    {
        $tagsToMoveBrAfter = ['del', 'em', 'strong'];
        // Filtrer chaque balise cible
        foreach ($tagsToMoveBrAfter as $tag) {
            $crawler->filter($tag)->each(function (Crawler $node) use ($tagsToMoveBrAfter) {
                // Récupérer le nœud courant
                $currentNode = $node->getNode(0);

                // Appeler récursivement sur les enfants du nœud
                $childCrawler = new Crawler($currentNode);
                $this->moveBrAfterTags($childCrawler, $tagsToMoveBrAfter);

                // Trouver tous les <br> dans le nœud
                $brNodes = [];
                foreach ($currentNode->childNodes as $child) {
                    if ($child->nodeName === 'br') {
                        $brNodes[] = $child; // Stocker les nœuds <br> à déplacer
                    }
                }

                // Déplacer les <br> après le nœud courant
                foreach ($brNodes as $brNode) {
                    // Déplacer le <br> après le nœud courant
                    if ($currentNode->nextSibling) {
                        $currentNode->parentNode->insertBefore($brNode, $currentNode->nextSibling);
                    } else {
                        $currentNode->parentNode->appendChild($brNode);
                    }
                    // Supprimer le <br> du nœud courant
                    $currentNode->removeChild($brNode);
                }
            });
        }
    }
}