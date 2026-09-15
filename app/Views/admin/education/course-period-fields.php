<div class="grid-span-2 education-course-advanced-grid">
    <div>
        <label class="form-label" for="course-start-date">Data de início do curso</label>
        <input class="form-control" id="course-start-date" type="date" name="starts_at" value="<?= e($periodCourse['starts_at'] ?? '') ?>">
    </div>
    <div>
        <label class="form-label" for="course-end-date">Data de término do curso</label>
        <input class="form-control" id="course-end-date" type="date" name="ends_at" value="<?= e($periodCourse['ends_at'] ?? '') ?>">
    </div>
    <div>
        <label class="form-label" for="course-hours">Carga horária total (horas)</label>
        <input class="form-control" id="course-hours" type="number" name="workload_hours" min="0.01" max="9999.99" step="0.01" value="<?= e($periodCourse['workload_hours'] ?? '') ?>" placeholder="Ex.: 40">
    </div>
</div>
