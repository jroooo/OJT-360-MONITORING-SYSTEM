<?php /* Employee dashboard page fragment; included by dashboard(employee).php. */ ?>
<!-- =================================================

                 COMPANIES

            ================================================== -->

            <section class="page" id="page-companies">

 

                <div class="hero">

                    <div>

                        <div class="eyebrow">Placement & Advisory</div>

                        <h1>Companies &amp; Assigned Students</h1>

                        <p>View partner companies, assigned students, total logged hours, absence records, and weekly journals.</p>
                    </div>
                </div>

 

                <?php if ($companiesFlash): ?>

                    <div class="interns-flash <?= ($companiesFlash["type"] ?? "") === "success" ? "success" : "error" ?>" role="<?= ($companiesFlash["type"] ?? "") === "error" ? "alert" : "status" ?>">

                        <?= htmlspecialchars($companiesFlash["message"] ?? "", ENT_QUOTES, "UTF-8") ?>

                    </div>

                <?php endif; ?>

 

                <div class="section-card">

                    <div class="section-head company-page-section-head">

                        <div>

                            <div class="section-kicker">Partner Organizations</div>

                            <h2 class="section-title">Companies &amp; Student Placement</h2>

                            <p class="section-sub">Assign student interns to companies and monitor their progress, absences, and journals.</p>

                        </div>

 

                        <div class="company-page-search">
                            <input class="search" id="companyPageSearch" placeholder="Search company or student...">
                        </div>

                    </div>

 

                    <div id="companyPlacementContainer">

                        <!-- Populated by JS -->

                    </div>

                </div>

 

            </section>

 

            