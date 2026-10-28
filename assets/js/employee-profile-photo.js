const employeeChangePhotoButton = document.getElementById("employeeChangePhoto");
const employeeProfilePhotoInput = document.getElementById("employeeProfilePhotoInput");

if (employeeChangePhotoButton && employeeProfilePhotoInput) {
    employeeChangePhotoButton.addEventListener("click", () => employeeProfilePhotoInput.click());

    employeeProfilePhotoInput.addEventListener("change", async () => {
        const photo = employeeProfilePhotoInput.files[0];
        if (!photo) return;

        const formData = new FormData();
        formData.append("profile_picture", photo);
        employeeChangePhotoButton.disabled = true;

        try {
            const response = await fetch("actions/employee_profile_photo.php", {
                method: "POST",
                headers: { "X-CSRF-Token": EMPLOYEE_PROFILE_PHOTO_CSRF },
                body: formData
            });

            let result;
            try {
                result = await response.json();
            } catch {
                throw new Error("The server returned an invalid response while saving the photo.");
            }

            if (!response.ok || !result.ok) {
                throw new Error(result.message || "The profile photo could not be saved.");
            }

            document.querySelectorAll(".employee-profile-avatar, #sidebarProfile .avatar, #topProfileBtn .avatar")
                .forEach(avatar => {
                    const image = document.createElement("img");
                    image.src = result.url;
                    image.alt = "";
                    avatar.replaceChildren(image);
                });
            showToast(result.message);
        } catch (error) {
            showToast(error instanceof Error ? error.message : "The profile photo could not be saved.");
        } finally {
            employeeChangePhotoButton.disabled = false;
            employeeProfilePhotoInput.value = "";
        }
    });
}
