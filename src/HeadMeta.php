<?php

namespace Developtus\Utils;

/**
 * Class HeadMeta
 * Manager and generator of html meta data
 * @package Core\Component
 * @method $this setName(string $name, string $content, array $extraAttribs = [])
 * @method mixed getName(string $name)
 * @method $this removeName(string $name)
 * @method $this setHttpEquiv(string $name, string $content, array $extraAttribs = [])
 * @method mixed getHttpEquiv(string $name)
 * @method $this removeHttpEquiv(string $name)
 * @method $this setItemprop(string $name, string $content, array $extraAttribs = [])
 * @method mixed getItemprop(string $name)
 * @method $this removeItemprop(string $name)
 * @method $this setProperty(string $name, string $content, array $extraAttribs = [])
 * @method mixed getProperty(string $name)
 * @method $this removeProperty(string $name)
 */
class HeadMeta
{
    /**
     * Meta key charset
     */
    const CHARSET = 'charset';

    /**
     * Meta key for names
     */
    const NAME = 'name';

    /**
     * Meta key for http-equiv's
     */
    const HTTP_EQUIV = 'http-equiv';

    /**
     * Meta key for itemprop's
     */
    const ITEMPROP = 'itemprop';

    /**
     * Meta key for open graph
     */
    const PROPERTY = 'property';

    /**
     * Meta link's
     */
    const LINK = 'link';

    /**
     * Custom meta data
     */
    const CUSTOM = 'custom';

    /**
     * Theoretical limit for meta descriptions
     * @see https://moz.com/blog/how-long-should-your-meta-description-be-2018
     * @var int
     */
    private static $metaDescriptionLength = 270;

    /**
     * Meta datas almacenadas
     * @var array
     */
    private $meta;

    /**
     * HeadMeta constructor.
     */
    public function __construct()
    {
        $this->meta = [
            self::CHARSET    => ['UTF-8'],
            self::NAME       => [],
            self::HTTP_EQUIV => [],
            self::ITEMPROP   => [],
            self::LINK       => [],
            self::CUSTOM     => [],
        ];
    }

    /**
     * @param null $key obtener una parte del meta, si es null devuelve todos los meta
     * @return array
     */
    public function getMetas($key = null): array
    {
        return $this->meta[$key] ?? $this->meta;
    }

    /**
     * Set charset
     * @param string $charset
     * @return HeadMeta
     */
    public function setCharset(string $charset): HeadMeta
    {
        $this->meta[self::CHARSET] = [$charset];

        return $this;
    }

    /**
     * Remove meta itemprop
     * @param string $href
     * @return HeadMeta
     */
    public function deleteLink(string $href): HeadMeta
    {
        if ($this->getName($href)) {
            unset($this->meta[self::LINK][$href]);
        }

        return $this;
    }

    /**
     * Get meta itemprop
     * @param string $href
     * @return array|bool
     */
    public function getLink(string $href)
    {
        return $this->meta[self::LINK][$href] ? ['href' => $href] + $this->meta[self::LINK][$href] : false;
    }

    /**
     * Set meta link
     * @param string $href
     * @param array $attribs
     * @return HeadMeta
     */
    public function setLink(string $href, array $attribs): HeadMeta
    {
        $this->meta[self::LINK][$href] = $attribs;

        return $this;
    }

    /**
     * @param $method
     * @param $content
     * @return mixed
     * @throws \BadMethodCallException método está mal formado
     * @throws \BadMethodCallException encabezado desconocido
     */
    public function __call($method, $content)
    {
        // check method called
        if (preg_match('/^(remove|set|get)([a-z]+)$/i', $method, $match) !== 1) {
            throw new \BadMethodCallException('Header not found');
        }

        // prepare operation
        $operation = strtolower($match[1]);
        $header = uncamelize($match[2], '-');

        // check supported header
        if (!isset($this->meta[$header])) {
            throw new \BadMethodCallException('Header not supported');
        }

        // set method
        if ($operation === 'set') {

            // check arguments
            if (!isset($content[0], $content[1])) {
                throw new \BadMethodCallException('Set method require 2 arguments');
            }

            return $this->$operation($header, $content[0], $content[1], $content[2] ?? []);
        }

        // check arguments
        if (!isset($content[0])) {
            throw new \BadMethodCallException('Get/Remove methods required name argument');
        }

        // get or remove
        return $this->$operation($header, $content[0]);
    }

    /**
     * Remove meta by name
     * @param string $meta
     * @param string $name
     * @return HeadMeta
     */
    public function remove(string $meta, string $name): HeadMeta
    {
        if (isset($this->meta[$meta][$name])) {
            unset($this->meta[$meta][$name]);
        }

        return $this;
    }

    /**
     * Get meta by name
     * @param string $meta
     * @param string $name
     * @return bool
     */
    public function get(string $meta, string $name)
    {
        return $this->meta[$meta][$name] ?? false;
    }

    /**
     * Set meta
     * @param string $meta
     * @param string $name
     * @param string $content
     * @param array $extraAttribs
     * @return HeadMeta
     */
    public function set(string $meta, string $name, string $content, array $extraAttribs = []): HeadMeta
    {
        $this->meta[$meta][$name] = ['content' => $content] + $extraAttribs;

        return $this;
    }

    /**
     * @param string $custom
     * @return HeadMeta
     */
    public function addCustom(string $custom): self
    {
        $this->meta[self::CUSTOM][] = $custom;

        return $this;
    }

    /**
     * @return array
     */
    public function getCustom(): array
    {
        return $this->meta[self::CUSTOM];
    }

    /**
     * @param string $custom
     * @return HeadMeta
     */
    public function setCustom(string $custom): self
    {
        $this->meta[self::CUSTOM] = [$custom];

        return $this;
    }

    /**
     * Set meta description, pass null to remove
     *
     * @param string|null $description
     * @return HeadMeta
     */
    public function setDescription(string $description = null): self
    {
        if ($description) {
            $this->setName('description', cutText($description, self::$metaDescriptionLength));
        } else {
            $this->removeName('description');
        }

        return $this;
    }

    /**
     * Generate html content <meta>
     * <code>
     *      <meta charset="UTF-8">
     *      <meta name="..." content="...">
     *      <meta itemprop="..." content="...">
     *      <meta property="..." content="..."> si hay soporte para Open Graph
     *      <meta http-equiv="..." content="...">
     *      <link href="..." attributes="values"...>
     * </code>
     * @return string
     */
    public function __toString(): string
    {
        $return = '';
        foreach ($this->meta as $tipo => $metas) {
            // charset is only charset="xxx"
            if ($tipo === self::CHARSET) {
                if (!empty(current($metas))) {
                    $return .= '<meta' . htmlAttributes([$tipo => current($metas)]) . '>';
                }
                continue;
            }

            // customs are considered to be well-formed
            if ($tipo === self::CUSTOM) {
                $return .= implode('', $metas);
                continue;
            }

            // links 
            if ($tipo === self::LINK) {
                foreach ($metas as $href => $attribs) {
                    $return .= '<link' . htmlAttributes(['href' => $href] + $attribs) . '>';
                }
                continue;
            }

            // generating <meta xxx="" content="">
            foreach ($metas as $key => $content) {
                $return .= '<meta' . htmlAttributes([$tipo => $key] + $content) . '>';
            }
        }

        return $return;
    }

    /**
     * Alias of __toString
     * @return string
     */
    public function __invoke(): string
    {
        return $this->__toString();
    }
}