import React, { useState, useEffect } from "react";
import { Modal, Spin, Card, Input, notification, Button } from "antd";
import { EditOutlined } from "@ant-design/icons";
import axios from "axios";

const { TextArea } = Input;

export default function Index({ pasien }) {
    const [modalBridgeOpen, setModalBridgeOpen] = useState(false);

    useEffect(() => {}, []);

    return (
        <>
            <Card>
                <Button>Bridge Data</Button>
            </Card>

            <Modal
                title="Bridging Data Ke INACBG"
                open={modalBridgeOpen}
            ></Modal>
        </>
    );
}
