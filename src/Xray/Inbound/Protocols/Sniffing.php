<?php

namespace XUI\Xray\Inbound\Protocols;

class Sniffing
{
    private bool $enabled;
    private array $dest_override = ['http', 'tls', 'quic', 'fakedns'];
    private bool $metadata_only = false;
    private array $domains_excluded = [];
    private bool $route_only = false;

    public function __construct(
        bool  $enabled = true, array $dest_override = ['http', 'tls', 'quic', 'fakedns'], bool $metadata_only = false,
        array $domains_excluded = [], bool $route_only = false
    )
    {
        $this->enabled = $enabled;
        $this->dest_override = $dest_override;
        $this->metadata_only = $metadata_only;
        $this->domains_excluded = $domains_excluded;
        $this->route_only = $route_only;
    }

    public function enabled(bool $status = null): bool
    {
        return $status ? $this->enabled = $status : $this->enabled;
    }

    public function dest_override(array $destinations = null): array
    {
        return $destinations ? $this->dest_override = $destinations : $this->dest_override;
    }

    public function metadata_only(bool $status = null): bool
    {
        return $status ? $this->metadata_only = $status : $this->metadata_only;
    }

    public function domains_excluded(array $domains = null): array
    {
        return $domains ? $this->domains_excluded = $domains : $this->domains_excluded;
    }

    public function route_only(bool $status = null): bool
    {
        return $status ? $this->route_only = $status : $this->route_only;
    }

    /**
     * Returns fully structured sniffing for xray json config
     * @return array
     */
    public function sniffing(): array
    {
        return [
            'enabled' => $this->enabled,
            'destOverride' => $this->dest_override,
            'metadataOnly' => $this->metadata_only,
            'domainsExcluded' => $this->domains_excluded,
            'routeOnly' => $this->route_only,
        ];
    }
}