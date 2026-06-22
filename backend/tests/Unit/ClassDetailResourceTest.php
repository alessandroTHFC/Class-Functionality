<?php

use App\Enums\NccdLevelEnum;
use App\Http\Resources\ClassDetailResource;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\Request;

// Unit tests for the NCCD summary calculation inside ClassDetailResource.
//
// These are unit tests rather than feature tests because the calculation logic lives
// entirely within the resource — it filters an already-loaded in-memory collection,
// so no database query is involved. Testing it in isolation is faster and more precise
// than asserting on a full HTTP response.
//
// We manually instantiate the resource and call toArray() directly, bypassing the HTTP stack.

describe('ClassDetailResource — nccd_summary', function () {

    // Helper: build a SchoolClass model with a students collection pre-loaded in memory.
    // setRelation() manually sets an Eloquent relationship on a model instance without
    // hitting the database — the resource's whenLoaded('students') will find this relation.
    beforeEach(function () {
        $this->makeClassWithStudents = function (array $levels): SchoolClass {
            $class    = new SchoolClass(['name' => 'Test Class']);
            $students = collect($levels)->map(
                fn ($level) => new Student(['nccd_level' => NccdLevelEnum::from($level)])
            );
            $class->setRelation('students', $students);
            $class->setRelation('yearLevel', null);
            $class->setRelation('createdBy', null);
            $class->setRelation('users', collect());

            return $class;
        };
    });

    // Each NCCD level should be counted correctly from the loaded students collection.
    // The resource filters using enum identity comparison — NccdLevelEnum::QDTP === NccdLevelEnum::QDTP.
    it('counts students correctly by nccd_level', function () {
        $class = ($this->makeClassWithStudents)([
            'QDTP',
            'QDTP',
            'Supplementary',
            'Substantial',
            'Substantial',
            'Substantial',
            'Extensive',
        ]);

        $resource = new ClassDetailResource($class);
        $data     = $resource->toArray(Request::create('/'));

        expect($data['nccd_summary']['QDTP'])->toBe(2)
            ->and($data['nccd_summary']['Supplementary'])->toBe(1)
            ->and($data['nccd_summary']['Substantial'])->toBe(3)
            ->and($data['nccd_summary']['Extensive'])->toBe(1);
    });

    // When no students have a given level, that level's count should be 0, not null or missing.
    it('returns zero for nccd levels with no students', function () {
        $class = ($this->makeClassWithStudents)(['QDTP', 'QDTP']);

        $resource = new ClassDetailResource($class);
        $data     = $resource->toArray(Request::create('/'));

        expect($data['nccd_summary']['Supplementary'])->toBe(0)
            ->and($data['nccd_summary']['Substantial'])->toBe(0)
            ->and($data['nccd_summary']['Extensive'])->toBe(0);
    });

    // Edge case: a class with no students enrolled should return all zeros.
    it('returns all zeros for an empty class', function () {
        $class = ($this->makeClassWithStudents)([]);

        $resource = new ClassDetailResource($class);
        $data     = $resource->toArray(Request::create('/'));

        expect($data['nccd_summary']['QDTP'])->toBe(0)
            ->and($data['nccd_summary']['Supplementary'])->toBe(0)
            ->and($data['nccd_summary']['Substantial'])->toBe(0)
            ->and($data['nccd_summary']['Extensive'])->toBe(0);
    });
});
