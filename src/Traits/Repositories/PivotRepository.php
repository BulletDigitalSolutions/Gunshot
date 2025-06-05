<?php

namespace BulletDigitalSolutions\Gunshot\Traits\Repositories;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait PivotRepository
{
    /**
     * @return string
     */
    public function getParentClassName()
    {
        return basename(str_replace('\\', '/', $this->parentClass));
    }

    /**
     * @return string
     */
    public function getChildClassName()
    {
        return basename(str_replace('\\', '/', $this->childClass));
    }

    /**
     * @return string
     */
    public function getParentGetter()
    {
        return Str::camel(sprintf('get %s', Str::kebab($this->getParentName())));
    }

    /**
     * @return string
     */
    public function getChildGetter()
    {
        return Str::camel(sprintf('get %s', Str::kebab($this->getChildName())));
    }

    /**
     * @return string
     */
    public function getParentSetter()
    {
        return Str::camel(sprintf('set %s', Str::kebab($this->getParentName())));
    }

    /**
     * @return string
     */
    public function getChildSetter()
    {
        return Str::camel(sprintf('set %s', Str::kebab($this->getChildName())));
    }

    /**
     * @return string
     */
    public function getParentName()
    {
        return Str::camel($this->getParentClassName());
    }

    /**
     * @return string
     */
    public function getChildName()
    {
        return Str::camel($this->getChildClassName());
    }

    /**
     * @param $pivot
     * @param $attributes
     * @return mixed
     */
    public function savePivotAttributes($pivot, $attributes = [])
    {
        return $pivot;
    }

    /**
     * @param $parent
     * @param $child
     * @return mixed
     */
    public function attach($attachingTo, $toAttach, $pivotAttributes = [])
    {
        $isAttachingToPrimary = true;

        if (is_array($toAttach)) {
            return $this->sync($attachingTo, $toAttach, $pivotAttributes);
        }

        if ($attachingTo instanceof $this->parentClass) {
            $existing = $this->findOneBy([$this->getParentName() => $attachingTo, $this->getChildName() => $toAttach]);
        } elseif ($attachingTo instanceof $this->childClass) {
            $isAttachingToPrimary = false;
            $existing = $this->findOneBy([$this->getChildName() => $attachingTo, $this->getParentName() => $toAttach]);
        } else {
            throw new \InvalidArgumentException('$attachingTo must be an instance of '.$this->parentClass.' or '.$this->childClass);
        }

        if ($isAttachingToPrimary) {
            $pivot = $this->savePivot($existing, array_merge($pivotAttributes, [
                'parent' => $attachingTo,
                'child' => $toAttach,
            ]));
        } else {
            $pivot = $this->savePivot($existing, array_merge($pivotAttributes, [
                'parent' => $toAttach,
                'child' => $attachingTo,
            ]));
        }

        $this->_em->refresh($attachingTo);

        return $attachingTo;
    }

    /**
     * @param $parent
     * @param $child
     * @return mixed
     */
    public function forceAttach($attachingTo, $toAttach, $pivotAttributes = [])
    {
        $isAttachingToPrimary = true;

        if ($isAttachingToPrimary) {
            $pivot = $this->savePivot(null, array_merge($pivotAttributes, [
                'parent' => $attachingTo,
                'child' => $toAttach,
            ]));
        } else {
            $pivot = $this->savePivot(null, array_merge($pivotAttributes, [
                'parent' => $toAttach,
                'child' => $attachingTo,
            ]));
        }

        $this->_em->refresh($attachingTo);

        return $attachingTo;
    }

    /**
     * @param $parent
     * @param $child
     * @return mixed
     */
    public function attachWithPivot($parent, $child, $pivotAttributes)
    {
        return $this->attach($parent, $child, $pivotAttributes);
    }

    /**
     * @param $pivot
     * @param $pivotAttributes
     * @return mixed|null
     */
    public function savePivot($pivot = null, $pivotAttributes = [])
    {
        if (! $pivot) {
            $pivot = new $this->_entityName;
        }

        if ($parent = Arr::get($pivotAttributes, 'parent')) {
            if (! $parent instanceof $this->parentClass) {
                $parent = app($this->parentClass)->getRepository()->find($parent);
            }

            $pivot->{$this->getParentSetter()}($parent);
        }

        if ($child = Arr::get($pivotAttributes, 'child')) {
            if (! $child instanceof $this->childClass) {
                $child = app($this->childClass)->getRepository()->find($child);
            }

            $pivot->{$this->getChildSetter()}($child);
        }

        $pivot = $this->savePivotAttributes($pivot, $pivotAttributes);

        $this->_em->persist($pivot);
        $this->_em->flush();

        return $pivot;
    }

    /**
     * @param $entity1
     * @param $entity2
     * @return void
     */
    public function detach($entity1, $entity2)
    {
        if (is_array($entity2)) {
            foreach ($entity2 as $entity) {
                $this->detach($entity1, $entity);
            }

            return $entity1;
        }

        $existing = $this->findByEntities($entity1, $entity2);

        foreach ($existing as $item) {
            $this->destroy($item);
        }

        $this->_em->refresh($entity1);

        return $entity1;
    }

    /**
     * @param $entity
     * @return void
     */
    public function detachByPivot($entity, array $pivotSearch = [])
    {
        $existing = $this->findByEntity($entity, $pivotSearch);

        foreach ($existing as $item) {
            $this->destroy($item);
        }

        $this->_em->refresh($entity);

        return $entity;
    }

    /**
     * @param $entity
     * @return mixed
     *
     * @throws \Exception
     */
    public function detachAll($entity)
    {
        $existing = collect($this->findByEntity($entity));
        $first = $existing?->first();

        foreach ($existing as $item) {
            $this->destroy($item);
        }

        return $first;
    }

    /**
     * @param $parent
     * @param $children
     * @return mixed
     */
    public function sync($attachingTo, $toAttach = [], $pivotAttributes = [])
    {
        $existing = collect($this->findByEntity($attachingTo));

        if ($attachingTo instanceof $this->parentClass) {
            $existing = $existing->map(function ($item) {
                return $item->{$this->getChildGetter()}();
            });
        } else {
            $existing = $existing->map(function ($item) {
                return $item->{$this->getParentGetter()}();
            });
        }

        if (count($toAttach) === 0) {
            $this->detachAll($attachingTo);

            return $attachingTo;
        }

        $toAttachCollection = collect();

        foreach ($toAttach as $attach) {
            // if $attach is a number
            if (is_numeric($attach)) {
                if ($attachingTo instanceof $this->parentClass) {
                    $attach = app($this->childClass)->getRepository()->find($attach);
                } else {
                    $attach = app($this->parentClass)->getRepository()->find($attach);
                }
            }

            $toAttachCollection->push($attach);
        }

        foreach ($toAttachCollection as $child) {
            if (! $existing->contains($child)) {
                $this->attach($attachingTo, $child, $pivotAttributes);
            }
        }

        foreach ($existing as $child) {
            if (! $toAttachCollection->contains($child)) {
                $this->detach($attachingTo, $child);
            }
        }

        $this->_em->refresh($attachingTo);

        return $attachingTo;
    }

    /**
     * @param $parent
     * @param $children
     * @param $pivotAttributes
     * @return mixed
     */
    public function syncWithPivot($parent, array $children, array $pivotAttributes)
    {
        $this->sync($parent, $children, $pivotAttributes);
    }

    /**
     * @param $entity1
     * @param $entity2
     * @return mixed
     *
     * @throws \Exception
     */
    public function findByEntities($entity1, $entity2, $pivotSearch = [])
    {
        if ($entity1 instanceof $this->parentClass) {
            return $this->findBy(array_merge([$this->getParentName() => $entity1, $this->getChildName() => $entity2], $pivotSearch));
        } elseif ($entity1 instanceof $this->childClass) {
            return $this->findBy(array_merge([$this->getChildName() => $entity1, $this->getParentName() => $entity2], $pivotSearch));
        } else {
            throw new \Exception('Entity must be an instance of '.$this->parentClass.' or '.$this->childClass);
        }
    }

    /**
     * @param $parent
     * @param $children
     * @return mixed
     */
    public function syncWithoutDetaching($attachingTo, $toAttach = [], $pivotAttributes = [])
    {
        $existing = collect($this->findByEntity($attachingTo));

        $existing = $existing->map(function ($item) {
            return $item->{$this->getChildGetter()}();
        });

        $toAttachCollection = collect();

        foreach ($toAttach as $attach) {
            if (! $attach instanceof $this->childClass) {
                $attach = app($this->childClass)->getRepository()->find($attach);
            }
            $toAttachCollection->push($attach);
        }

        foreach ($toAttachCollection as $child) {
            if (! $existing->contains($child)) {
                $this->attach($attachingTo, $child, $pivotAttributes);
            } else {
                $this->updateExistingPivot($attachingTo, $child, $pivotAttributes);
            }
        }

        $this->_em->refresh($attachingTo);

        return $attachingTo;
    }

    /**
     * @param $attachingTo
     * @param $toAttach
     * @param $pivotAttributes
     * @return mixed
     */
    public function syncWithoutDetach($attachingTo, $toAttach = [], $pivotAttributes = [])
    {
        return $this->syncWithoutDetaching($attachingTo, $toAttach, $pivotAttributes);
    }

    /**
     * @param $parent
     * @param $child
     * @param $pivotAttributes
     * @return mixed|null
     */
    public function updateExistingPivot($entity1, $entity2, $pivotAttributes = [])
    {
        $existing = $this->findByEntities($entity1, $entity2);

        foreach ($existing as $pivot) {
            $newPivot = $this->savePivot($pivot, $pivotAttributes);

            $this->_em->persist($newPivot);
            $this->_em->flush();
        }

        return $newPivot;
    }

    /**
     * @param $entity
     * @return mixed
     *
     * @throws \Exception
     */
    public function findByEntity($entity, $pivotSearch = [])
    {
        if ($entity instanceof $this->childClass) {
            return $this->findBy(array_merge([$this->getChildName() => $entity], $pivotSearch));
        }

        if ($entity instanceof $this->parentClass) {
            return $this->findBy(array_merge([$this->getParentName() => $entity], $pivotSearch));
        }

        throw new \Exception('Entity must be an instance of '.$this->childClass.' or '.$this->parentClass);
    }

    /**
     * @param $entity1
     * @param $entity2
     * @return bool
     *
     * @throws \Exception
     */
    public function isAttached($entity1, $entity2)
    {
        return count($this->findByEntities($entity1, $entity2)) > 0;
    }

    /**
     * @param $entity
     * @return mixed
     *
     * @throws \Exception
     */
    public function getRelated($entity)
    {
        $pivots = collect($this->findByEntity($entity));

        if ($entity instanceof $this->parentClass) {
            return $pivots->map(function ($pivot) {
                return $pivot->{$this->getChildGetter()}();
            });
        } else {
            return $pivots->map(function ($pivot) {
                return $pivot->{$this->getParentGetter()}();
            });
        }
    }

    public function syncPivot($attachingTo, $toAttach = [], $column = 'id', $pivotIdValue = 'id')
    {
        $existing = collect($this->findByEntity($attachingTo));
        $toAttachCollection = collect();

        if ($attachingTo instanceof $this->parentClass) {
            $existing = $existing->map(function ($item) {
                return $item->{$this->getChildGetter()}();
            });
        } else {
            $existing = $existing->map(function ($item) {
                return $item->{$this->getParentGetter()}();
            });
        }

        foreach ($toAttach as $item) {
            if ($attachingTo instanceof $this->parentClass) {
                $child = app($this->childClass)->getRepository()->findOneBy([$column => $item[$pivotIdValue]]);
            } else {
                $child = app($this->parentClass)->getRepository()->findOneBy([$column => $item[$pivotIdValue]]);
            }

            if (! $existing->contains($child)) {
                $this->attach($attachingTo, $child, $item);
            } else {
                $this->updateExistingPivot($attachingTo, $child, $item);
            }

            $toAttachCollection->push($child);
        }

        foreach ($existing as $child) {
            if (! $toAttachCollection->contains($child)) {
                $this->detach($attachingTo, $child);
            }
        }

        $this->_em->refresh($attachingTo);

        return $attachingTo;
    }
}
