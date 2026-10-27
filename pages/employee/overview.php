<?php /* Employee dashboard page fragment; included by dashboard(employee).php. */ ?>
<!-- =================================================

                 OVERVIEW

            ================================================== -->

            <section class="page active" id="page-overview">

 

                <div class="hero">

                    <div class="eyebrow">Employee Workspace</div>

                    <h1>Overview & Analytics</h1>

                    <p>Review student profiles, company metrics, internship outcomes, and system analytics.</p>

                </div>

 

                <div class="stat-grid">

                    <div class="stat-card">

                        <div class="stat-icon">A</div>

                        <div>

                            <div class="stat-label">Active Companies</div>

                            <div class="stat-value" id="activeCompanyCount"><?= count(array_filter($companyRecords, static fn(array $companyRecord): bool => $companyRecord["status"] === "active")) ?></div>

                            <div class="stat-sub">Available for assignment</div>

                        </div>

                    </div>

 

                    <div class="stat-card">

                        <div class="stat-icon amber">A</div>

                        <div>

                            <div class="stat-label">Inactive Companies</div>

                            <div class="stat-value" id="inactiveCompanyCount"><?= count(array_filter($companyRecords, static fn(array $companyRecord): bool => $companyRecord["status"] === "inactive")) ?></div>

                            <div class="stat-sub">Not accepting placements</div>

                        </div>

                    </div>

 

                    <div class="stat-card">

                        <div class="stat-icon green">A</div>

                        <div>

                            <div class="stat-label">Registered Students</div>

                            <div class="stat-value"><?= count($studentRecords) ?></div>

                            <div class="stat-sub">From students database</div>

                        </div>

                    </div>

 

                    <div class="stat-card">

                        <div class="stat-icon purple">U</div>

                        <div>

                            <div class="stat-label">Schools</div>

                            <div class="stat-value"><?= $studentSchoolCount ?></div>

                            <div class="stat-sub">Unique schools represented</div>

                        </div>

                    </div>

                </div>

 

                <div class="stat-grid" style="margin-top: 16px;">

                    <div class="stat-card">

                        <div class="stat-icon">H</div>

                        <div>

                            <div class="stat-label">Hours Logged</div>

                            <div class="stat-value">8,214</div>

                            <div class="stat-sub">+12.5% this semester</div>

                        </div>

                    </div>

 

                    <div class="stat-card">

                        <div class="stat-icon">J</div>

                        <div>

                            <div class="stat-label">Journal Submission</div>

                            <div class="stat-value">92%</div>

                            <div class="stat-sub">Above 85% target</div>

                        </div>

                    </div>

 

                    <div class="stat-card">

                        <div class="stat-icon purple">E</div>

                        <div>

                            <div class="stat-label">Evaluation Avg.</div>

                            <div class="stat-value"><?= $evaluationStats["average"] !== null ? number_format($evaluationStats["average"], 2) . " / 5" : "—" ?></div>

                            <div class="stat-sub"><?= $evaluationStats["count"] ?> evaluations · <?= $evaluationStats["completed"] ?> completed</div>

                        </div>

                    </div>

 

                    <div class="stat-card">

                        <div class="stat-icon green">C</div>

                        <div>

                            <div class="stat-label">Completion Forecast</div>

                            <div class="stat-value">96%</div>

                            <div class="stat-sub">On-time projection</div>

                        </div>

                    </div>

                </div>

 

                <div class="report-grid" style="margin-top: 20px;">

                    <div class="chart-card">

                        <div class="chart-title">Internship progress</div>

                        <div class="chart-sub">Hours logged by month</div>

 

                        <div class="bars">

                            <div class="bar-col"><div class="bar" style="height:32%"></div><div class="bar-label">Oct</div></div>

                            <div class="bar-col"><div class="bar" style="height:45%"></div><div class="bar-label">Nov</div></div>

                            <div class="bar-col"><div class="bar" style="height:41%"></div><div class="bar-label">Dec</div></div>

                            <div class="bar-col"><div class="bar" style="height:62%"></div><div class="bar-label">Jan</div></div>

                            <div class="bar-col"><div class="bar" style="height:58%"></div><div class="bar-label">Feb</div></div>

                            <div class="bar-col"><div class="bar" style="height:78%"></div><div class="bar-label">Mar</div></div>

                        </div>

                    </div>

 

                    <div class="chart-card">

                        <div class="chart-title">Submission health</div>

                        <div class="chart-sub">Current journal submissions</div>

 

                        <div class="donut-wrap">

                            <div class="donut">

                                <div class="donut-text">92%</div>

                            </div>

                        </div>

 

                        <div class="legend">

                            <span>Submitted 184</span>

                            <span>Late 12</span>

                            <span>Not submitted 4</span>

                        </div>

                    </div>

                </div>

 

                <div class="section-card" style="margin-top:24px;">

                    <div class="section-head">

                        <div>

                            <div class="section-kicker">Student Overview</div>

                            <h2 class="section-title">Registered Students</h2>

                            <p class="section-sub">Student profiles and current OJT progress summary.</p>

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

                                    <th>Working Hours</th>

                                </tr>

                            </thead>

                            <tbody id="assignmentTable">

                                <?php if ($internPageError !== ""): ?>

                                    <tr><td colspan="7"><div class="empty">Student records could not be loaded.</div></td></tr>

                                <?php elseif (!$studentRecords): ?>

                                    <tr><td colspan="7"><div class="empty">No registered students found.</div></td></tr>

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

                                            $studentProgress = $studentProgressByStudentId[$studentRecord["student_id"]] ?? null;

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

                                            <td>

                                                <?php if ($studentProgressPageError !== ""): ?>

                                                    Unavailable

                                                <?php elseif ($studentProgress === null): ?>

                                                    Unavailable

                                                <?php else: ?>

                                                    <?= number_format($studentProgress["completedHours"], 1) ?> hrs

                                                <?php endif; ?>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </section>

 

            