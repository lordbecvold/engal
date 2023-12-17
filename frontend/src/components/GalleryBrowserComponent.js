import React, { useEffect, useState } from 'react';

// import engal utils
import { getApiUrl } from '../utils/ApiUtils';
import { getUserToken } from '../utils/AuthUtils';

// import engal components
import LoadingComponent from './sub-components/LoadingComponent';
import CustomErrorComponent from './errors/CustomErrorComponent';

export default function GalleryBrowserComponent(props) {
    // state variables for managing component state
    const [error, setError] = useState(null);
    
    // content data
    const [image_list, setImageList] = useState([]);
    const [image_content_list, setImageContentList] = useState([]);
    const [current_index, setCurrentIndex] = useState(0);

    // get user token
    let user_token = getUserToken();

    // get api url
    let api_url = getApiUrl();

    useEffect(function() {
        async function fetchData() { // fetch images list
            try {
                const formData = new FormData();
                
                // set request data
                formData.append('token', user_token);
                formData.append('gallery', props.gallery_name);

                // fetch response
                const response = await fetch(api_url + '/images', {
                    method: 'POST',
                    body: formData,
                });

                // decode data
                const data = await response.json();

                // check if request is success
                if (data.status === 'success') {
                    setImageList(Object.entries(data.images));
                } else {
                    setError(data.message);
                    console.error('Error fetching image list: ' + data.message);
                }
            } catch (error) {
                setError(error);
                console.error('Error fetching image list: ' + error);
            }     
        };

        fetchData();
    }, [api_url, user_token, props.gallery_name]);

    async function fetchNextImageContent() { // fetch image content
        if (current_index < image_list.length) {
            const [id, name] = image_list[current_index];

            try {
                const formData = new FormData();
                
                // set post data
                formData.append('token', user_token);
                formData.append('gallery', props.gallery_name);
                formData.append('image', name);

                // fetch response
                const response = await fetch(api_url + '/image/content', {
                    method: 'POST',
                    body: formData,
                });

                // get response data
                const data = await response.json();

                if (data.status === 'success') {
                    setImageContentList((prev_list) => [
                        ...prev_list,
                        {id, name, content: data.content}
                    ]);
                } else {
                    setError(data.message);
                    console.error('Error fetching image content: ' + data.message);
                }
            } catch (error) {
                setError(error);
                console.error('Error fetching image content: ' + error);
            }

            setCurrentIndex((prev_index) => prev_index + 1);
        }
    };

    useEffect(function() {
        if (current_index < image_list.length) {
            fetchNextImageContent();
        }
    }, [current_index, image_list, fetchNextImageContent]);

    // check if image loaded 
    if (image_list.length !== image_content_list.length) {
        return <LoadingComponent/>;
    } else {
        if (error !== null) {
            return <CustomErrorComponent error_message={error}/>;
        } else {
            return (
                <div>
                    {image_content_list.map(({ id, name, content }) => (
                    <div key={id}>
                        <p>{`Name: ${name}`}</p>
                        <img src={'data:image/jpg;base64,' + content} alt='' />
                    </div>
                    ))}
                </div>
            );
        }
    }
}
