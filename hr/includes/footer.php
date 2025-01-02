</body>
    <script src="./assets/acad.js" ></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

	<?php
  if(isset($_SESSION['status']) && $_SESSION['status_code'] !='') {
      ?>
      <script>
        const Toast = Swal.mixin({
          toast: true,
          position: "top-end",
          showConfirmButton: false,
          timer: 3000,
          timerProgressBar: true,
          didOpen: (toast) => {
            toast.onmouseenter = Swal.stopTimer;
            toast.onmouseleave = Swal.resumeTimer;
          }
        });
        Toast.fire({
          icon: "<?php echo $_SESSION['status_code']; ?>",
          title: "<?php echo $_SESSION['status']; ?>"
        });
      </script>
      <?php
      unset($_SESSION['status']);
      unset($_SESSION['status_code']);
  }     
?>

    <script>
      let profileDropdownList = document.querySelector(".profile-dropdown-list");
      let btn = document.querySelector(".profile-dropdown-btn");

      let classList = profileDropdownList.classList;

      const toggle = () => classList.toggle("active");

      window.addEventListener("click", function (e) {
      if (!btn.contains(e.target)) classList.remove("active");
      });
    

		document.addEventListener('DOMContentLoaded', () => {
    const importButton = document.getElementById('importButton');
    const importModal = document.getElementById('importModal');
    const closeButton = importModal.querySelector('.close');

    importButton.addEventListener('click', () => {
        importModal.style.display = 'block';
    });

    closeButton.addEventListener('click', () => {
        importModal.style.display = 'none';
    });

    window.addEventListener('click', (event) => {
        if (event.target === importModal) {
            importModal.style.display = 'none';
        }
    });
});


function previewImage(event) {
        const file = event.target.files[0];
        const reader = new FileReader();

        reader.onload = function(e) {
            const imagePreview = document.getElementById('imagePreview');
            imagePreview.innerHTML = `<img src="${e.target.result}" alt="Image Preview" style="max-width: 200px; max-height: 200px; border-radius: 25px;">`;
        };

        if (file) {
            reader.readAsDataURL(file);
        } else {
            document.getElementById('imagePreview').innerHTML = '';
        }
    }


  document.getElementById('importForm').addEventListener('submit', function() {
  var submitButton = document.getElementById('submitBtn');
  submitButton.disabled = true;
  submitButton.innerHTML = 'Submitting...'; // Optional: Change the button text
});


	</script>
  <script>
    function getAcademicInfo() {
    const currentDate = new Date();
    const currentMonth = currentDate.getMonth() + 1; // getMonth() returns month from 0-11
    const currentYear = currentDate.getFullYear();

    let semester, schoolYear;

    // Determine the current semester based on the month
    if (currentMonth >= 6 && currentMonth <= 10) {
        semester = "First Semester";
        schoolYear = `${currentYear}-${currentYear + 1}`;
    } else if (currentMonth >= 11 || currentMonth <= 3) {
        semester = "Second Semester";
        schoolYear = `${currentYear - 1}-${currentYear}`;
    } else if (currentMonth >= 4 && currentMonth <= 5) {
        semester = "Summer Term";
        schoolYear = `${currentYear - 1}-${currentYear}`;
    }

    // Display semester and school year in the HTML
    document.getElementById('currentSemester').textContent = semester;
    document.getElementById('schoolYear').textContent = schoolYear;
  }

  // Run the function when the page loads
  window.onload = getAcademicInfo;

  </script>

</html>