<?php

/*
 * This file is part of the LyraAdminBundle package.
 *
 * Copyright 2011 Massimo Giagnoni <gimassimo@gmail.com>
 *
 * This source file is subject to the MIT license. Full copyright and license
 * information are in the LICENSE file distributed with this source code.
 */

namespace Lyra\AdminBundle\Model\ORM;

use Lyra\AdminBundle\Model\ModelManager as BaseManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * Generic model manager class (Doctrine ORM).
 */
class ModelManager extends BaseManager
{
    protected $em;

    protected $class;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function find($id)
    {
        return $this->getRepository()->find($id);
    }

    public function findByIds(array $ids)
    {
        $qb = $this->getRepository()->createQueryBuilder('a');
        $qb->where($qb->expr()->in('a.id', $ids));

        return $qb->getQuery()->getResult();
    }

    public function setFieldValueByIds($field, $value, array $ids)
    {
        $qb = $this->getRepository()->createQueryBuilder('a');
        $qb->update()
            ->set('a.'.$field, $qb->expr()->literal($value))
            ->where($qb->expr()->in('a.id', $ids));

        return $qb->getQuery()->execute();
    }

    public function getBaseListQueryBuilder()
    {
        $qb = $this->getRepository()
            ->createQueryBuilder('a')
            ->select('a');

        return $qb;
    }

    public function getRepository()
    {
        return $this->em->getRepository($this->class);
    }

    public function save($object)
    {
        $this->em->persist($object);
        $this->em->flush();

        return true;
    }

    public function remove($object)
    {
        $this->em->remove($object);
        $this->em->flush();
    }

    public function removeByIds(array $ids)
    {
        $objects = $this->findByIds($ids);

        foreach ($objects as $object) {
            $this->em->remove($object);
        }

        $this->em->flush();
    }

    public function getFieldsInfo()
    {
        $metadata = $this->em->getClassMetadata($this->class);
        $fields = array();

        foreach ($metadata->fieldMappings as $name => $attrs) {
            $fields[$name]['name'] = $name;
            $fields[$name]['type'] = $attrs['type'];
            if (isset($attrs['id']) && $attrs['id'] === true) {
                $fields[$name]['id'] = true;
            }
            if (isset($attrs['length'])) {
                $fields[$name]['length'] = $attrs['length'];
            }
        }

        foreach ($metadata->associationMappings as $name => $attrs) {
            if (ClassMetadataInfo::MANY_TO_ONE == $attrs['type'] || ClassMetadataInfo::MANY_TO_MANY == $attrs['type']) {
                $fields[$name]['type'] = 'entity';
                $fields[$name]['options'] = array(
                    'class' => $attrs['targetEntity'],
                    'multiple' => ClassMetadataInfo::MANY_TO_MANY == $attrs['type']
                );
            }
        }

        return $fields;
    }
}
