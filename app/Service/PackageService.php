<?php
namespace App\Service;

use App\Model\Package;
use Hyperf\Cache\Annotation\CacheEvict;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Di\Annotation\Inject;
use Psr\SimpleCache\CacheInterface;

class PackageService
{
    /**
     * @Inject
     * @var CacheInterface
     */
    private $cache;

    public function getById(int $id): ?Package
    {
        return Package::find($id);
    }

    #[Cacheable(prefix: "packages", ttl: 3600, value: "all")]
    public function getAll(): ?array
    {
        return Package::all()->toArray();
    }

    #[CacheEvict(prefix: "packages", value: "all")]
    public function create(array $data): Package
    {
        $package = new Package();
        $package->name = $data['name'];
        $package->quota = $data['quota'];
        $package->duration = $data['duration'];
        $package->price = $data['price'];
        $package->save();

        return $package;
    }

    #[CacheEvict(prefix: "packages", value: "all")]
    public function update(int $id, array $data): ?Package
    {
        $package = Package::find($id);
        if (!$package) {
            return null;
        }

        $package->name = $data['name'] ?? $package->name;
        $package->quota = $data['quota'] ?? $package->quota;
        $package->duration = $data['duration'] ?? $package->duration;
        $package->price = $data['price'] ?? $package->price;
        $package->save();

        return $package;
    }

    #[CacheEvict(prefix: "packages", value: "all")]
    public function delete(int $id): bool
    {
        $package = Package::find($id);
        if (!$package) {
            return false;
        }

        $package->delete();

        return true;
    }
}
