import React, { useState } from "react";
import { Popover, Row, Col, Button  } from "antd";

export default function Index({ pasien }) {
    const [open, setOpen] = useState(false);
    const hide = () => {
        setOpen(false);
    };
    const handleOpenChange = (newOpen) => {
        setOpen(newOpen);
    };

    return (
        <>
            <Popover
                title={`${pasien.FTKD_PASIEN} - ${pasien.NAMAPASIEN}`}
                trigger="click"
                content={
                    <Row gutter={[8, 8]} style={{ width: 250 }}>
                        <Col span={12}>
                            <Button
                                block
                                size="small"
                                onClick={() => alert(JSON.stringify(pasien))}
                            >
                                SEP
                            </Button>
                        </Col>

                        <Col span={12}>
                            <Button
                                block
                                size="small"
                                onClick={() => alert(JSON.stringify(pasien))}
                            >
                                Resume
                            </Button>
                        </Col>

                        <Col span={12}>
                            <Button
                                block
                                size="small"
                                onClick={() => alert(JSON.stringify(pasien))}
                            >
                                Grouping
                            </Button>
                        </Col>

                        <Col span={12}>
                            <Button
                                block
                                size="small"
                                onClick={() => alert(JSON.stringify(pasien))}
                            >
                                Klaim
                            </Button>
                        </Col>
                    </Row>
                }
            >
                <Button type="primary" size="small">
                    Klaim
                </Button>
            </Popover>
        </>
    );
}
