<?php

use App\Models\SchoolClass;
use App\Models\Student;

// These tests verify the student sync behaviour that happens through PUT /api/classes/{class}.
// Students are added and removed exclusively through the update flow — there is no separate
// delete-student endpoint. The frontend always sends the full desired student list.

describe('Student sync via PUT /api/classes/{class}', function () {

    // Creates a class with one student, then updates it with two students.
    // Verifies the new student is added and the original student is still present.
    it('adds new students when student_ids includes IDs not yet in the class', function () {
        $class          = SchoolClass::factory()->create();
        $existingStudent = Student::factory()->create(['tenant_id' => test()->tenant->id]);
        $newStudent      = Student::factory()->create(['tenant_id' => test()->tenant->id]);

        $class->students()->sync([$existingStudent->id]);

        actingAsRole('coordinator')
            ->putJson("/api/classes/{$class->id}", [
                'name'        => $class->name,
                'student_ids' => [$existingStudent->id, $newStudent->id],
            ])
            ->assertOk();

        $this->assertDatabaseHas('class_students', ['class_id' => $class->id, 'student_id' => $newStudent->id]);
        $this->assertDatabaseHas('class_students', ['class_id' => $class->id, 'student_id' => $existingStudent->id]);
    });

    // sync() removes any pivot rows whose IDs are not in the submitted array.
    // Submitting only $newStudent means $existingStudent is removed.
    it('removes students omitted from the student_ids array', function () {
        $class           = SchoolClass::factory()->create();
        $existingStudent = Student::factory()->create(['tenant_id' => test()->tenant->id]);
        $newStudent      = Student::factory()->create(['tenant_id' => test()->tenant->id]);

        $class->students()->sync([$existingStudent->id]);

        actingAsRole('coordinator')
            ->putJson("/api/classes/{$class->id}", [
                'name'        => $class->name,
                'student_ids' => [$newStudent->id],
            ])
            ->assertOk();

        $this->assertDatabaseMissing('class_students', ['class_id' => $class->id, 'student_id' => $existingStudent->id]);
        $this->assertDatabaseHas('class_students', ['class_id' => $class->id, 'student_id' => $newStudent->id]);
    });

    // An empty array is a valid sync payload — it removes all enrolled students.
    // This is intentional: the frontend multi-select can be cleared before saving.
    it('removes all students when student_ids is an empty array', function () {
        $class   = SchoolClass::factory()->create();
        $student = Student::factory()->create(['tenant_id' => test()->tenant->id]);

        $class->students()->sync([$student->id]);

        actingAsRole('coordinator')
            ->putJson("/api/classes/{$class->id}", [
                'name'        => $class->name,
                'student_ids' => [],
            ])
            ->assertOk();

        $this->assertDatabaseMissing('class_students', ['class_id' => $class->id, 'student_id' => $student->id]);
    });

    // UpdateClassRequest::authorize() checks 'edit class' — read-only lacks this permission.
    it('returns 403 when read-only tries to update student enrolment', function () {
        $class   = SchoolClass::factory()->create();
        $student = Student::factory()->create(['tenant_id' => test()->tenant->id]);

        actingAsRole('read-only')
            ->putJson("/api/classes/{$class->id}", [
                'name'        => $class->name,
                'student_ids' => [$student->id],
            ])
            ->assertForbidden();
    });
});
