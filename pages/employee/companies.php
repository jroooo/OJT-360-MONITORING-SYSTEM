<?php /* Employee dashboard page fragment; included by dashboard(employee).php. */ ?>
<!-- =================================================

                 COMPANIES

            ================================================== -->

            <section class="page" id="page-companies">

 

                <div class="hero">

                    <div>

                        <div class="eyebrow">Placement & Advisory</div>

                        <h1>Companies &amp; Interns</h1>

                        <p>Manage company placements and review each intern’s progress and records.</p>
                    </div>
                </div>

 

                <?php if ($companiesFlash): ?>

                    <div class="interns-flash <?= ($companiesFlash["type"] ?? "") === "success" ? "success" : "error" ?>" role="<?= ($companiesFlash["type"] ?? "") === "error" ? "alert" : "status" ?>">

                        <?= htmlspecialchars($companiesFlash["message"] ?? "", ENT_QUOTES, "UTF-8") ?>

                    </div>

                <?php endif; ?>

                <div class="company-summary-grid" id="companySummary" aria-label="Company placement summary"></div>

                <div class="section-card">

                    <div class="section-head company-page-section-head">

                        <div>

                            <div class="section-kicker">Partner Organizations</div>

                            <h2 class="section-title">Company &amp; Intern Placement</h2>
                            <p class="section-sub">Assign interns to partner companies and review their progress.</p>

                        </div>

 

                        <div class="company-page-controls">
                            <button class="small-btn primary company-global-assign-button" id="companyGlobalAssignButton" type="button" onclick="openInternAssignment()">+ Assign Intern</button>
                            <div class="company-page-search">
                                <input class="search" id="companyPageSearch" placeholder="Search company or student...">
                            </div>
                        </div>

                    </div>

 

                    <div id="companyPlacementContainer">

                        <!-- Populated by JS -->

                    </div>

                </div>

 

            </section>

 

            