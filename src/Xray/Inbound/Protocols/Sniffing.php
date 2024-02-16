<?php

namespace XUI\Xray\Inbound\Protocols;

use JSON\json;

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

    public function enabled(bool $status = null): bool|null
    {
        if (is_null($status)) {
            return $this->enabled;
        } else {
            $this->enabled = $status;
        }
    }

    public function dest_override(array $destinations = null): array|null
    {
        if (is_null($destinations)) {
            return $this->dest_override;
        } else {
            $this->dest_override = $destinations;
        }
    }

    public function metadata_only(bool $status = null): bool|null
    {
        if (is_null($status)) {
            return $this->metadata_only;
        } else {
            $this->metadata_only = $status;
        }
    }

    public function domains_excluded(array $domains = null): array|null
    {
        if (is_null($domains)) {
            return $this->domains_excluded;
        } else {
            $this->domains_excluded = $domains;
        }
    }

    public function route_only(bool $status = null): bool|null
    {
        if (is_null($status)) {
            return $this->route_only;
        } else {
            $this->route_only = $status;
        }
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