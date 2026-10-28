<?php /* Employee dashboard page fragment; included by dashboard(employee).php. */ ?>
<!-- =================================================

                 INTERNS

            ================================================== -->

            <section class="page" id="page-interns">

 

                <div class="hero">

                    <div>

                        <div class="eyebrow">Placement Management</div>

                        <h1>Intern Students</h1>

                        <p>Student profiles loaded from the registered students database.</p>

                    </div>

 

                </div>

 

                <?php if ($internsFlash): ?>

                    <div class="interns-flash <?= ($internsFlash["type"] ?? "") === "success" ? "success" : "error" ?>" role="<?= ($internsFlash["type"] ?? "") === "error" ? "alert" : "status" ?>">

                        <?= htmlspecialchars($internsFlash["message"] ?? "", ENT_QUOTES, "UTF-8") ?>

                    </div>

                <?php endif; ?>

 

                <?php if ($internPageError !== ""): ?>

                    <div class="interns-flash error" role="alert">

                        <?= htmlspecialchars($internPageError, ENT_QUOTES, "UTF-8") ?>

                    </div>

                <?php endif; ?>

 

                <div class="stat-grid">
                    <div class="stat-card">
                        <div class="stat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="9" cy="8" r="4"/>
                                <path d="M2 21v-2a7 7 0 0 1 14 0v2m3-13a4 4 0 0 1 0 8m1 2a5 5 0 0 1 2 4"/>
                            </svg>
                        </div>
                        <div class="interns-stat-content">
                            <div class="stat-label">Registered Students</div>
                            <div class="stat-value"><?= count($studentRecords) ?></div>
                            <div class="stat-sub">Student Data</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 3h9l4 4v14H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/>
                                <path d="M14 3v5h5M8 12h8m-8 4h8"/>
                            </svg>
                        </div>
                        <div class="interns-stat-content">
                            <div class="stat-label">Course</div>
                            <div class="stat-value">BSIT</div>
                            <div class="stat-sub">Bachelor of Science in Information Technology</div>
                        </div>
                    </div>

                    <div class="stat-card interns-school-card">
                        <div class="stat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m3 9 9-6 9 6M5 10v10h14V10M3 21h18"/>
                                <path d="M9 20v-6h6v6M8 11h.01M12 11h.01M16 11h.01"/>
                            </svg>
                        </div>
                        <div class="interns-stat-content">
                            <div class="stat-label">School</div>
                            <div class="stat-value">Leyte Normal University</div>
                            <div class="stat-sub">Registered students' school</div>
                        </div>
                    </div>
                </div>

 

                <div class="section-card">

                    <div class="section-head">

                        <div>

                            <div class="section-kicker">Student Information</div>

                            <h2 class="section-title">Registered Students</h2>

                            <p class="section-sub">Profile and registration details from the students database.</p>

                        </div>

                    </div>

 

                    <div class="table-wrap">

                        <table>

                            <thead>

                                <tr>

                                    <th>Student</th>

                                    <th>Student ID</th>

                                    <th>Course</th>

                                    <th>Institutional Email</th>

                                    <th>Mobile Number</th>

                                    <th>School</th>

                                    <th>Registered</th>

                                    <th>Action</th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php if ($internPageError !== ""): ?>

                                    <tr><td colspan="8"><div class="empty">Student records could not be loaded.</div></td></tr>

                                <?php elseif (!$studentRecords): ?>

                                    <tr><td colspan="8"><div class="empty">No registered students found.</div></td></tr>

                                <?php else: ?>

                                    <?php foreach ($studentRecords as $studentRecord): ?>

                                        <?php

                                            $studentName = trim(

                                                $studentRecord["first_name"] . " " .

                                                ($studentRecord["middle_name"] !== "" ? $studentRecord["middle_name"] . " " : "") .

                                                $studentRecord["last_name"]

                                            );

                                            $studentInitials = strtoupper(
                                                substr($studentRecord["first_name"], 0, 1) .
                                                substr($studentRecord["last_name"], 0, 1)
                                            );
                                            $registeredAt = strtotime($studentRecord["created_at"]);

                                        ?>

                                        <tr>

                                            <td>

                                                <div class="person">

                                                    <div class="mini-avatar"><?= htmlspecialchars($studentInitials, ENT_QUOTES, "UTF-8") ?></div>

                                                    <div class="person-name"><?= htmlspecialchars($studentName, ENT_QUOTES, "UTF-8") ?></div>

                                                </div>

                                            </td>

                                            <td><?= htmlspecialchars($studentRecord["student_id"], ENT_QUOTES, "UTF-8") ?></td>

                                            <td><?= htmlspecialchars($studentRecord["course"], ENT_QUOTES, "UTF-8") ?></td>

                                            <td><?= htmlspecialchars($studentRecord["institutional_email"], ENT_QUOTES, "UTF-8") ?></td>

                                            <td><?= htmlspecialchars($studentRecord["mobile_number"], ENT_QUOTES, "UTF-8") ?></td>

                                            <td><?= htmlspecialchars($studentRecord["school_name"], ENT_QUOTES, "UTF-8") ?></td>

                                            <td><?= $registeredAt === false ? "—" : date("M j, Y", $registeredAt) ?></td>

                                            <td>

                                                <form class="remove-student-form" method="post" action="dashboard(employee).php?section=interns" data-student-name="<?= htmlspecialchars($studentName, ENT_QUOTES, "UTF-8") ?>">

                                                    <input type="hidden" name="student_action" value="delete">

                                                    <input type="hidden" name="students_csrf" value="<?= $studentsCsrf ?>">

                                                    <input type="hidden" name="student_database_id" value="<?= (int)$studentRecord["id"] ?>">

                                                    <button class="small-btn danger" type="submit">Remove</button>

                                                </form>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </section>

 

            