const addFormToCollection = (e) => {
    const collectionHolder = document.querySelector('.' + e.currentTarget.dataset.collectionHolderClass);
  
    const item = document.createElement('li');
  
    item.innerHTML = collectionHolder
      .dataset
      .prototype
      .replace(
        /__name__/g,
        collectionHolder.dataset.index
      );
  
    collectionHolder.appendChild(item);
  
    collectionHolder.dataset.index++;
   
  };
//   const addTagFormDeleteLink = (item) => {
//     const removeFormButton = document.createElement('button');
//     removeFormButton.innerText = 'Delete this tag';

//     item.append(removeFormButton);

//     removeFormButton.addEventListener('click', (e) => {
//         e.preventDefault();
//         // remove the li for the tag form
//         item.remove();
//     });
// }
  document
    .querySelectorAll('.add_video_link')
    .forEach(btn => {
        btn.addEventListener("click", addFormToCollection)
    });

    // document
    // .querySelectorAll('ul.videos li')
    // .forEach((tag) => {
    //     addTagFormDeleteLink(tag)
    // })   