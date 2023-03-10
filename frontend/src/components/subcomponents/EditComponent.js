// react
import { useState } from "react";

// application config
import {API_URL, API_TOKEN, ENCRYPTION_TOKEN} from "../../config.js"

// app components
import LoadingComponent from "../subcomponents/LoadingComponent.js"

import CryptoJS from 'crypto-js'

const EditComponent = () => {

    // default info box content
    const [info, setInfo] = useState("")

    // get url parameters
    let urlParams = new URLSearchParams(window.location.search);

    // get imageID name paramater
    let imageID = urlParams.get("id");
    
    // image list use state
    const [galleryList, setGalleryList] = useState(null)
    const [canGetList, setCanGetList] = useState(true)

    // get image list
    const setGalleryListByAPI = async () => {

        const response = await fetch(API_URL + "?token=" + API_TOKEN + "&action=allGaleryNames")

        const data = await response.json() 

        setGalleryList(data)

        setCanGetList(false)
    }

    if (canGetList) {
        setGalleryListByAPI()
    }

    // image data get use state
    const [imageName, setImageName] = useState("...")
    const [imageContent, setImageContent] = useState("...")
    const [canGetImageData, setCanGetImageData] = useState(true)

    // get image data
    const setImageDataByAPI = async () => {

        const response = await fetch(API_URL + "?token=" + API_TOKEN + "&action=imageContent&id=" + imageID)

        const data = await response.json() 

        if (ENCRYPTION_TOKEN != null) {
            setImageContent(CryptoJS.AES.decrypt(data.content, ENCRYPTION_TOKEN).toString(CryptoJS.enc.Utf8))
            setImageName(CryptoJS.AES.decrypt(data.name, ENCRYPTION_TOKEN).toString(CryptoJS.enc.Utf8))
        } else {
            setImageContent(data.content)
            setImageName(data.name)
        }

        setCanGetImageData(false)
    }

    if (canGetImageData) {
        setImageDataByAPI()
    }

    const editor = () => {
    
        // get from data
        let imgName = document.getElementById("name").value
        let imgGallery = document.getElementById("gallery").value
    
        // check if data is not empty
        if (imgName.length === 0) {
            
            // set info
            setInfo(<p className="info-text">Error: image name is empty!</p>)
        
        } else if (imgGallery.length === 0) {
                            
            // set info
            setInfo(<p className="info-text">Error: gallery: is empty!</p>)
    
        } else {
        
            // get new imag name
            let newImageName = imgName

            // encrypt new image name
            if (ENCRYPTION_TOKEN != null) {
                newImageName = CryptoJS.AES.encrypt(imgName, ENCRYPTION_TOKEN).toString()
            }

            // edit url 
            let urlBuilder = API_URL + "?token=" + API_TOKEN + "&action=edit&id=" + imageID + "&name=" + newImageName + "&galleryName=" + imgGallery
            
            // send get to API with fetch lol
            fetch(urlBuilder)
            
            // redirect to gallery
            setTimeout(() => {
                window.location.replace("/browse/?gallery=" + imgGallery + "&startby=0");
            }, 1000);
        }
    
    }

    // return image edit form
    if ((galleryList != null) && (imageContent != "...")) {

        return (
            <div className="editor">
                <div className="info-box">{info}</div>
                <p className="editor-title">Edit photo: {imageName}</p>
                <img className="prew-img" src={imageContent}/><br/>
                <input className="text-input" id="name" type="text" name="name" placeholder="New name"/><br/>
                <input className="text-input" id="gallery" type="text" name="gallery" list="galleries" placeholder="new gallery"/><br/>
                <datalist id="galleries">
                    {galleryList.map(name => <option key={name}>{name}</option>)}
                </datalist>
                <button className="submit-button" onClick={editor}>Save</button>
            </div>
        )
    } else {
        return (<LoadingComponent/>)
    }
}

export default EditComponent