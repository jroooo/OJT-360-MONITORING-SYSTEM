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

                <?php if ($placementCompanyPageError !== ""): ?>

                    <div class="interns-flash error" role="alert">

                        <?= htmlspecialchars($placementCompanyPageError, ENT_QUOTES, "UTF-8") ?>

                    </div>

                <?php endif; ?>

 

                <div class="stat-grid overview-company-stats">

                    <div class="stat-card">

                        <div class="stat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 21h18M5 21V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v16M15 10h3a2 2 0 0 1 2 2v9"/>
                                <path d="M8 7h1m2 0h1m-4 4h1m2 0h1m-4 4h1m2 0h1m4-1h1m-1 4h1"/>
                            </svg>
                        </div>

                        <div>

                            <div class="stat-label">Registered Companies</div>

                            <div class="stat-value"><?= $placementCompanyPageError !== "" ? "—" : count($placementCompanyRecords) ?></div>

                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="9" cy="8" r="4"/>
                                <path d="M2 21v-2a7 7 0 0 1 14 0v2m3-13a4 4 0 0 1 0 8m1 2a5 5 0 0 1 2 4"/>
                            </svg>
                        </div>

                        <div>

                            <div class="stat-label">Registered Students</div>

                            <div class="stat-value"><?= count($studentRecords) ?></div>

                            <div class="stat-sub">Student Data</div>

                        </div>

                    </div>

 

                    <div class="stat-card overview-school-card">

                        <div class="stat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m3 9 9-6 9 6M5 10v10h14V10M3 21h18"/>
                                <path d="M9 20v-6h6v6M8 11h.01M12 11h.01M16 11h.01"/>
                            </svg>
                        </div>

                        <div>

                            <div class="stat-label">Schools</div>

                            <div class="stat-value">Leyte Normal University</div>

                            <div class="stat-sub">Registered students' school</div>

                        </div>

                    </div>

                </div>

 

                <div class="stat-grid" style="margin-top: 16px;">

                    <div class="stat-card">

                        <div class="stat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="9"/>
                                <path d="M12 7v5l3 2"/>
                            </svg>
                        </div>

                        <div>

                            <div class="stat-label">Hours Logged</div>

                            <div class="stat-value">8,214</div>

                            <div class="stat-sub">+12.5% this semester</div>

                        </div>

                    </div>

 

                    <div class="stat-card">

                        <div class="stat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 3h9l4 4v14H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/>
                                <path d="M14 3v5h5M8 12h8m-8 4h8"/>
                            </svg>
                        </div>

                        <div>

                            <div class="stat-label">Journal Submission</div>

                            <div class="stat-value">92%</div>

                            <div class="stat-sub">Above 85% target</div>

                        </div>

                    </div>

 

                    <div class="stat-card">

                        <div class="stat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M8 4H5a2 2 0 0 0-2 2v15h18V6a2 2 0 0 0-2-2h-3"/>
                                <path d="M8 2h8v4H8z"/>
                                <path d="m8 14 2.5 2.5L16 11"/>
                            </svg>
                        </div>

                        <div>

                            <div class="stat-label">Evaluation Avg.</div>

                            <div class="stat-value"><?= $evaluationStats["average"] !== null ? number_format($evaluationStats["average"], 2) . " / 5" : "—" ?></div>

                            <div class="stat-sub"><?= $evaluationStats["count"] ?> evaluations · <?= $evaluationStats["completed"] ?> completed</div>

                        </div>

                    </div>

 

                    <div class="stat-card">

                        <div class="stat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 3v18h18M7 14l4-4 3 3 6-7"/>
                                <path d="M15 6h5v5"/>
                            </svg>
                        </div>

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

 

            </section>

 

            